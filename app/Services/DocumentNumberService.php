<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberService
{
    private const TYPE_ALIASES = [
        'purchase_request' => 'PR',
        'purchase_order' => 'PO',
        'receiving' => 'RCV',
        'sales_order' => 'SO',
        'delivery_order' => 'DO',
        'warehouse_transfer' => 'TRF',
        'stock_adjustment' => 'ADJ',
        'production' => 'PRD',
    ];

    private const LEGACY_TYPES = [
        'PR' => 'purchase_request',
        'PO' => 'purchase_order',
        'RCV' => 'receiving',
        'SO' => 'sales_order',
        'DO' => 'delivery_order',
        'TRF' => 'warehouse_transfer',
        'ADJ' => 'stock_adjustment',
        'PRD' => 'production',
    ];

    private const DOCUMENT_TABLES = [
        'PR' => 'purchase_requests',
        'PO' => 'purchase_orders',
        'RCV' => 'receivings',
        'SO' => 'sales_orders',
        'DO' => 'delivery_orders',
        'TRF' => 'warehouse_transfers',
        'ADJ' => 'stock_adjustments',
        'PRD' => 'productions',
    ];

    public function generate(string $documentType, ?Carbon $date = null): string
    {
        $date ??= now();
        $documentType = $this->normalizeDocumentType($documentType);

        return DB::transaction(function () use ($documentType, $date): string {
            $sequence = $this->sequence($documentType);

            if (! $sequence) {
                throw new RuntimeException("Active document sequence not found for {$documentType}.");
            }

            $period = $this->period($sequence->period_type, $date);
            $existingLastNumber = $this->existingLastNumber($sequence, $date);
            $lastNumber = $sequence->current_period === $period ? (int) $sequence->last_number : 0;
            $lastNumber = max($lastNumber, $existingLastNumber);
            $nextNumber = $lastNumber + 1;

            $sequence->update([
                'current_period' => $period,
                'last_number' => $nextNumber,
            ]);

            return $this->format($sequence, $date, $nextNumber);
        });
    }

    private function period(string $periodType, Carbon $date): string
    {
        return match ($periodType) {
            'daily' => $date->format('Ymd'),
            'yearly' => $date->format('Y'),
            default => $date->format('Ym'),
        };
    }

    private function format(DocumentSequence $sequence, Carbon $date, int $number): string
    {
        $parts = match ($sequence->period_type) {
            'daily' => [$sequence->prefix, $date->format('Y'), $date->format('m'), $date->format('d')],
            'yearly' => [$sequence->prefix, $date->format('Y')],
            default => [$sequence->prefix, $date->format('Y'), $date->format('m')],
        };

        $parts[] = str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT);

        return implode($sequence->separator, $parts);
    }

    private function normalizeDocumentType(string $documentType): string
    {
        $normalized = trim($documentType);

        return self::TYPE_ALIASES[$normalized] ?? strtoupper($normalized);
    }

    private function sequence(string $documentType): ?DocumentSequence
    {
        $sequence = DocumentSequence::where('document_type', $documentType)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if ($sequence) {
            return $sequence;
        }

        $legacyType = self::LEGACY_TYPES[$documentType] ?? null;

        if (! $legacyType) {
            return null;
        }

        $sequence = DocumentSequence::where('document_type', $legacyType)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if ($sequence) {
            $sequence->update(['document_type' => $documentType]);
            $sequence->refresh();
        }

        return $sequence;
    }

    private function existingLastNumber(DocumentSequence $sequence, Carbon $date): int
    {
        $table = self::DOCUMENT_TABLES[$sequence->document_type] ?? null;

        if (! $table) {
            return 0;
        }

        $prefix = implode($sequence->separator, match ($sequence->period_type) {
            'daily' => [$sequence->prefix, $date->format('Y'), $date->format('m'), $date->format('d')],
            'yearly' => [$sequence->prefix, $date->format('Y')],
            default => [$sequence->prefix, $date->format('Y'), $date->format('m')],
        });

        return DB::table($table)
            ->where('number', 'like', $prefix.$sequence->separator.'%')
            ->pluck('number')
            ->map(function (string $number) use ($sequence): int {
                $parts = explode($sequence->separator, $number);

                return (int) end($parts);
            })
            ->max() ?? 0;
    }
}
