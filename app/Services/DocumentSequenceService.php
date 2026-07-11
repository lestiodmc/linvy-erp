<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\DocumentSequenceCounter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentSequenceService
{
    private const ALIASES = [
        'PR' => 'PURCHASE_REQUEST',
        'PO' => 'PURCHASE_ORDER',
        'RCV' => 'GOODS_RECEIPT',
        'SO' => 'SALES_ORDER',
        'DO' => 'DELIVERY_ORDER',
        'ADJ' => 'STOCK_ADJUSTMENT',
        'TRF' => 'WAREHOUSE_TRANSFER',
        'BAS' => 'BATCH_ASSIGNMENT',
        'PRD' => 'PRODUCTION_ORDER',
        'purchase_request' => 'PURCHASE_REQUEST',
        'purchase_order' => 'PURCHASE_ORDER',
        'receiving' => 'GOODS_RECEIPT',
        'sales_order' => 'SALES_ORDER',
        'delivery_order' => 'DELIVERY_ORDER',
        'stock_adjustment' => 'STOCK_ADJUSTMENT',
        'warehouse_transfer' => 'WAREHOUSE_TRANSFER',
        'batch_assignment' => 'BATCH_ASSIGNMENT',
        'production' => 'PRODUCTION_ORDER',
    ];

    public function generate(string $code, ?int $companyId = null, ?int $branchId = null, ?Carbon $date = null): string
    {
        $date ??= now();
        $code = $this->normalizeCode($code);

        try {
            return DB::transaction(function () use ($code, $companyId, $branchId, $date): string {
                $sequence = $this->findSequence($code, $companyId, $branchId, true);

                if (! $sequence) {
                    throw new RuntimeException("Document sequence untuk kode {$code} belum dibuat atau tidak aktif.");
                }

                $period = $this->period($sequence->reset_type, $date);
                $counter = $this->lockedCounter($sequence, $companyId, $branchId, $period);
                $nextNumber = (int) $counter->last_number + 1;

                $counter->update(['last_number' => $nextNumber]);

                $sequence->forceFill([
                    'current_period' => $period,
                    'last_number' => $nextNumber,
                ])->save();

                return $this->format($sequence, $date, $nextNumber);
            }, 5);
        } catch (QueryException $exception) {
            if ($this->isDuplicateKey($exception)) {
                throw new RuntimeException('Nomor dokumen sudah digunakan. Silakan ulangi proses.', previous: $exception);
            }

            throw $exception;
        }
    }

    public function preview(DocumentSequence $sequence, ?int $companyId = null, ?int $branchId = null, ?Carbon $date = null): string
    {
        $date ??= now();
        $lastNumber = $this->currentCounter($sequence, $companyId, $branchId, $date);

        return $this->format($sequence, $date, $lastNumber + 1);
    }

    public function currentCounter(DocumentSequence $sequence, ?int $companyId = null, ?int $branchId = null, ?Carbon $date = null): int
    {
        if (! $sequence->exists) {
            return 0;
        }

        $date ??= now();
        $period = $this->period($sequence->reset_type, $date);

        if ($sequence->relationLoaded('counters')) {
            return (int) ($sequence->counters
                ->first(fn (DocumentSequenceCounter $counter): bool =>
                    $counter->period === $period
                    && (int) $counter->company_id === (int) $companyId
                    && (int) $counter->branch_id === (int) $branchId
                )?->last_number ?? 0);
        }

        return (int) (DocumentSequenceCounter::query()
            ->where('document_sequence_id', $sequence->id)
            ->where('period', $period)
            ->where(function ($query) use ($companyId): void {
                $companyId === null
                    ? $query->whereNull('company_id')
                    : $query->where('company_id', $companyId);
            })
            ->where(function ($query) use ($branchId): void {
                $branchId === null
                    ? $query->whereNull('branch_id')
                    : $query->where('branch_id', $branchId);
            })
            ->value('last_number') ?? 0);
    }

    private function lockedCounter(DocumentSequence $sequence, ?int $companyId, ?int $branchId, string $period): DocumentSequenceCounter
    {
        $query = DocumentSequenceCounter::query()
            ->where('document_sequence_id', $sequence->id)
            ->where('period', $period)
            ->where(function ($query) use ($companyId): void {
                $companyId === null
                    ? $query->whereNull('company_id')
                    : $query->where('company_id', $companyId);
            })
            ->where(function ($query) use ($branchId): void {
                $branchId === null
                    ? $query->whereNull('branch_id')
                    : $query->where('branch_id', $branchId);
            });

        $counter = (clone $query)->lockForUpdate()->first();

        if ($counter) {
            return $counter;
        }

        DocumentSequenceCounter::create([
            'document_sequence_id' => $sequence->id,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'period' => $period,
            'last_number' => 0,
        ]);

        return $query->lockForUpdate()->firstOrFail();
    }

    private function findSequence(string $code, ?int $companyId, ?int $branchId, bool $lock): ?DocumentSequence
    {
        $query = DocumentSequence::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('company_id');

                if ($companyId !== null) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id');

                if ($branchId !== null) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->orderByRaw('case when branch_id is null then 0 else 1 end desc')
            ->orderByRaw('case when company_id is null then 0 else 1 end desc');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function period(string $resetType, Carbon $date): string
    {
        return match ($resetType) {
            'never' => 'ALL',
            'yearly' => $date->format('Y'),
            default => $date->format('Ym'),
        };
    }

    private function format(DocumentSequence $sequence, Carbon $date, int $number): string
    {
        $datePart = $this->formatDatePart($sequence->date_format ?: 'YYYYMM', $date);
        $sequencePart = str_pad((string) $number, (int) ($sequence->digits ?: 5), '0', STR_PAD_LEFT);
        $separator = $sequence->separator ?? '-';

        return collect([$sequence->prefix, $datePart, $sequencePart])
            ->filter(fn (?string $part): bool => filled($part))
            ->implode($separator);
    }

    private function formatDatePart(string $dateFormat, Carbon $date): string
    {
        return strtr($dateFormat, [
            'YYYY' => $date->format('Y'),
            'YY' => $date->format('y'),
            'MM' => $date->format('m'),
            'DD' => $date->format('d'),
        ]);
    }

    private function normalizeCode(string $code): string
    {
        $normalized = trim($code);

        return self::ALIASES[$normalized] ?? strtoupper($normalized);
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000';
    }
}
