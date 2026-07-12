<?php

namespace App\Support;

final class QuantityFormatter
{
    public static function display(string|int|float|null $quantity, int $scale = 6): string
    {
        if ($quantity === null || $quantity === '') {
            return '0';
        }

        $value = number_format((float) $quantity, $scale, '.', '');

        return rtrim(rtrim($value, '0'), '.') ?: '0';
    }
}
