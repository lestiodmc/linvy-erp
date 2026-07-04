<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModuleSetting;
use App\Support\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleSettingController extends Controller
{
    public function index(): View
    {
        return view('settings.module_settings.index', [
            'package' => request('package'),
            'activePackage' => ModuleManager::packageName(),
            'packages' => config('linvy.packages'),
            'settings' => ModuleSetting::orderBy('id')->get()->keyBy('module'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $package = $request->input('package');
        $enabledModules = array_keys($request->input('modules', []));

        if ($package && array_key_exists($package, config('linvy.packages'))) {
            $enabledModules = config("linvy.packages.$package.modules");
        }

        foreach (config('linvy.optional_modules') as $module) {
            ModuleSetting::updateOrCreate(
                ['module' => $module],
                [
                    'label' => str($module)->replace('-', ' ')->replace('_', ' ')->title(),
                    'enabled' => in_array($module, $enabledModules, true),
                ]
            );
        }

        return redirect()->route('module-settings.index')->with('status', 'Module settings updated.');
    }
}
