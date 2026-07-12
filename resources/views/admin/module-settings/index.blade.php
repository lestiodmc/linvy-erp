<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-900">Module Settings</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('module-settings.update') }}" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                @csrf
                @method('PUT')

                <div class="border-b border-gray-100 px-6 py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-900">Package Presets</h3>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-800">Active: {{ $activePackage }}</span>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        @foreach($packages as $key => $preset)
                            <label class="rounded-lg border border-gray-200 p-4 text-sm hover:border-emerald-300">
                                <input type="radio" name="package" value="{{ $key }}" class="mr-2 border-gray-300 text-emerald-700 focus:ring-emerald-600">
                                <span class="font-semibold text-gray-900">{{ $preset['label'] }}</span>
                                <span class="mt-2 block text-gray-600">{{ implode(', ', array_map(fn ($module) => str($module)->replace('_', ' ')->title(), $preset['modules'])) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="px-6 py-5">
                    <h3 class="text-base font-semibold text-gray-900">Manual Module Visibility</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @foreach(config('linvy.optional_modules') as $module)
                            @php $setting = $settings->get($module); @endphp
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                                <input type="checkbox" name="modules[{{ $module }}]" value="1" @checked($setting?->enabled ?? config("linvy.default_enabled_modules.$module")) class="mt-1 rounded border-gray-300 text-emerald-700 focus:ring-emerald-600">
                                <span>
                                    <span class="block font-semibold text-gray-900">{{ $setting?->label ?? str($module)->replace('_', ' ')->title() }}</span>
                                    <span class="mt-1 block text-sm text-gray-600">
                                        @if($module === 'accounting')
                                            Enables account mapping and accounting account UI. Inventory remains independent.
                                        @elseif($module === 'production')
                                            Enables repacking and production order screens.
                                        @else
                                            Core operational module for {{ str($module)->replace('_', ' ')->title() }} workflows.
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-6 py-4">
                    <button type="submit" class="button-primary">Save Module Settings</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
