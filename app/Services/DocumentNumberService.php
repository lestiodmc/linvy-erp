<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberService
{
    public function generate(string $documentType, ?Carbon $date = null): string
    {
        $date ??= now();

        return DB::transaction(function () use ($documentType, $date): string {
            $sequence = DocumentSequence::where('document_type', $documentType)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException("Active document sequence not found for {$documentType}.");
            }

            $period = $this->period($sequence->period_type, $date);
            $lastNumber = $sequence->current_period === $period ? $sequence->last_number : 0;
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
}
