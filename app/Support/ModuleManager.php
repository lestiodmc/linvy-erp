<?php

namespace App\Support;

use App\Models\ModuleSetting;
use Illuminate\Support\Facades\Schema;

class ModuleManager
{
    public static function enabled(string $module): bool
    {
        if (! in_array($module, config('linvy.optional_modules', []), true)) {
            return true;
        }

        if (! Schema::hasTable('module_settings')) {
            return (bool) config("linvy.default_enabled_modules.$module", false);
        }

        return ModuleSetting::where('module', $module)->value('enabled')
            ?? (bool) config("linvy.default_enabled_modules.$module", false);
    }

    public static function packageName(): string
    {
        $production = self::enabled('production');
        $accounting = self::enabled('accounting');

        return match (true) {
            $production && $accounting => 'Complete',
            $production => 'Standard',
            default => 'Starter',
        };
    }
}
