@php
    $modules = config('linvy.modules');
    $user = Auth::user();
    $moduleEnabled = fn (string $module): bool => \App\Support\ModuleManager::enabled($module);

    $itemActive = fn (array $item): bool => collect($item['routes'] ?? [])->contains(fn ($pattern) => request()->routeIs($pattern));
    $moduleActive = function (array $module) use ($itemActive): bool {
        if (isset($module['routes']) && collect($module['routes'])->contains(fn ($pattern) => request()->routeIs($pattern))) {
            return true;
        }

        return collect($module['items'] ?? [])->contains(fn ($item) => $itemActive($item));
    };
@endphp

<aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 lg:translate-x-0 lg:shadow-none" :class="{ 'translate-x-0': sidebarOpen }">
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-100 px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="h-9 w-auto fill-current text-emerald-700" />
            <div>
                <div class="text-sm font-bold uppercase tracking-wide text-slate-900">Linvy ERP</div>
                <div class="text-xs text-slate-500">{{ \App\Support\ModuleManager::packageName() }} Package</div>
            </div>
        </a>
        <button type="button" class="rounded-md p-2 text-slate-400 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = false">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-5">
        @if($user?->canAccessModule('dashboard'))
            <a href="{{ route('dashboard') }}" class="mb-2 flex items-center rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-slate-50 hover:text-slate-950' }}">
                Dashboard
            </a>
        @endif

        <div class="space-y-5">
            @foreach($modules as $moduleKey => $module)
                @continue($moduleKey === 'dashboard' || ! $moduleEnabled($moduleKey) || ! $user?->canAccessModule($moduleKey))

                <div>
                    <div class="px-3 text-xs font-bold uppercase tracking-wide {{ $moduleActive($module) ? 'text-emerald-700' : 'text-slate-400' }}">{{ $module['label'] }}</div>
                    <div class="mt-2 space-y-1">
                        @foreach($module['items'] ?? [] as $item)
                            <a href="{{ route($item['route']) }}" class="flex items-center justify-between rounded-md px-3 py-2 text-sm font-medium transition {{ $itemActive($item) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                                <span>{{ $item['label'] }}</span>
                                @if($itemActive($item))
                                    <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </nav>
</aside>
