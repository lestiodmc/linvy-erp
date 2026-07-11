<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class InventoryExpiryStatus
{
    public const NEAR_EXPIRY_DAYS = 30;

    public static function status(mixed $expiryDate): string
    {
        if (blank($expiryDate)) return 'NO_EXPIRY';
        $expiry = Carbon::parse($expiryDate)->startOfDay();
        if ($expiry->lt(now()->startOfDay())) return 'EXPIRED';
        if ($expiry->lte(now()->startOfDay()->addDays(self::NEAR_EXPIRY_DAYS))) return 'NEAR_EXPIRY';
        return 'VALID';
    }

    public static function badge(string $status): string
    {
        return match ($status) {
            'EXPIRED' => 'bg-red-50 text-red-700 ring-red-100',
            'NEAR_EXPIRY' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'VALID' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }
}
