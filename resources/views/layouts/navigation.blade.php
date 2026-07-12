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

    $moduleIcons = [
        'master-data' => 'M4 6h16M4 12h16M4 18h10',
        'purchase' => 'M9 12h6m-6 4h6M7 4h10l2 4v12H5V8l2-4Z',
        'inventory' => 'M3 21h18M5 21V7l7-4 7 4v14',
        'production' => 'M9 3h6v4H9V3Zm-4 8h14v10H5V11Zm4 4h6',
        'sales' => 'M4 19V5m0 14h16M8 16l3-3 2 2 5-7',
        'accounting' => 'M4 6h16M4 10h16M7 14h2m4 0h4M6 18h12',
        'settings' => 'M10.5 6h3m-7 6h11m-9 6h7',
    ];

    $defaultOpen = collect($modules)
        ->filter(fn ($module, $key) => $key !== 'dashboard' && $moduleEnabled($key) && $user?->canAccessModule($key))
        ->mapWithKeys(fn ($module, $key) => [$key => $moduleActive($module)])
        ->toArray();

    // Presentation-only grouping. Menu definitions, routes, and visibility remain in config/linvy.php.
    $sectionForItem = function (string $moduleKey, string $label): string {
        return match ($moduleKey) {
            'inventory' => match ($label) {
                'Inventory Dashboard' => 'Dashboard',
                'Stock Balances', 'Item Ledger' => 'Inquiries',
                default => 'Transactions',
            },
            'purchase' => 'Transactions',
            'production' => 'Setup',
            'sales' => 'Transactions',
            'accounting' => 'Configuration',
            'settings' => 'Administration',
            'master-data' => match ($label) {
                'Companies', 'Branches', 'Warehouse Types', 'Warehouses' => 'Organization',
                'Items', 'Item Categories', 'Unit of Measure', 'Brands' => 'Product Data',
                'Suppliers', 'Customers' => 'Business Partners',
                default => 'Commercial Setup',
            },
            default => 'General',
        };
    };

    $groupedItems = fn (string $moduleKey, array $module) => collect($module['items'] ?? [])
        ->groupBy(fn (array $item) => $sectionForItem($moduleKey, $item['label']));
@endphp

<aside
    x-data="{ openGroups: @js($defaultOpen) }"
    class="theme-sidebar fixed inset-y-0 left-0 z-50 flex -translate-x-full flex-col shadow-2xl transition-all duration-300 lg:translate-x-0"
    :class="[sidebarOpen ? 'translate-x-0' : '', sidebarCollapsed ? 'w-20' : 'w-72']"
>
    <div class="theme-sidebar sticky top-0 z-10 flex h-16 shrink-0 items-center border-b border-white/10 px-4" :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <span class="theme-primary grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-sm font-black shadow-lg">L</span>
            <div class="min-w-0" x-show="!sidebarCollapsed" x-cloak>
                <div class="truncate text-sm font-black uppercase tracking-wide text-white">Linvy ERP</div>
                <div class="theme-sidebar-muted truncate text-xs font-semibold">{{ \App\Support\ModuleManager::packageName() }} Package</div>
            </div>
        </a>
        <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false" x-show="!sidebarCollapsed">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="erp-sidebar-scroll flex-1 overflow-y-auto px-3 py-4" aria-label="Primary navigation">
        @if($user?->canAccessModule('dashboard'))
            <a href="{{ route('dashboard') }}" class="erp-nav-module group relative mb-3 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold {{ request()->routeIs('dashboard') ? 'theme-sidebar-active erp-nav-item-active' : 'theme-sidebar-muted' }}" :class="sidebarCollapsed ? 'justify-center' : ''" :title="sidebarCollapsed ? 'Dashboard' : ''" aria-label="Dashboard">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl {{ request()->routeIs('dashboard') ? 'bg-white/15' : 'bg-white/5' }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z" /></svg>
                </span>
                <span x-show="!sidebarCollapsed" x-cloak>Dashboard</span>
                <span x-show="sidebarCollapsed" x-cloak class="erp-nav-tooltip">Dashboard</span>
            </a>
        @endif

        <div class="space-y-3">
            @foreach($modules as $moduleKey => $module)
                @continue($moduleKey === 'dashboard' || ! $moduleEnabled($moduleKey) || ! $user?->canAccessModule($moduleKey))

                <div class="border-t border-white/5 pt-2 first:border-t-0 first:pt-0">
                    <button
                        type="button"
                        class="erp-nav-module group relative flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-bold {{ $moduleActive($module) ? 'theme-sidebar-surface text-white' : 'theme-sidebar-muted' }}"
                        :class="sidebarCollapsed ? 'justify-center' : 'justify-between'"
                        :title="sidebarCollapsed ? '{{ $module['label'] }}' : ''"
                        @click="sidebarCollapsed ? setSidebar(false) : openGroups['{{ $moduleKey }}'] = !openGroups['{{ $moduleKey }}']"
                    >
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl {{ $moduleActive($module) ? 'theme-sidebar-active' : 'bg-white/5 theme-sidebar-muted' }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $moduleIcons[$moduleKey] ?? 'M5 7h14M5 12h14M5 17h14' }}" /></svg>
                            </span>
                            <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ $module['label'] }}</span>
                            <span x-show="sidebarCollapsed" x-cloak class="erp-nav-tooltip">{{ $module['label'] }}</span>
                        </span>
                        <svg x-show="!sidebarCollapsed" x-cloak class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="openGroups['{{ $moduleKey }}'] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                        </svg>
                    </button>

                    <div x-show="!sidebarCollapsed && openGroups['{{ $moduleKey }}']" x-transition x-cloak class="mt-1.5 pl-10">
                        @foreach($groupedItems($moduleKey, $module) as $section => $items)
                            <div class="erp-nav-section {{ ! $loop->first ? 'mt-3 border-t border-white/5 pt-3' : '' }}">
                                @if($section !== 'Dashboard')
                                    <div class="mb-1 px-3 text-[9px] font-black uppercase tracking-[0.16em] theme-sidebar-muted">{{ $section }}</div>
                                @endif
                                <div class="space-y-0.5">
                                    @foreach($items as $item)
                                        <a href="{{ route($item['route']) }}" class="erp-nav-item group flex items-center justify-between rounded-lg px-3 py-1.5 text-[13px] font-semibold {{ $itemActive($item) ? 'theme-sidebar-active erp-nav-item-active' : 'theme-sidebar-muted' }}">
                                            <span class="truncate">{{ $item['label'] }}</span>
                                            @if($itemActive($item))
                                                <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                            @else
                                                <span class="erp-nav-dot h-1.5 w-1.5 rounded-full"></span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </nav>

    <div class="theme-sidebar sticky bottom-0 z-10 border-t border-white/10 p-3">
        <button type="button" class="erp-nav-module group relative hidden w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold theme-sidebar-muted lg:flex" :class="sidebarCollapsed ? 'justify-center' : ''" @click="setSidebar(!sidebarCollapsed)" :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'" :title="sidebarCollapsed ? 'Expand Sidebar' : ''">
            <span class="grid h-8 w-8 place-items-center rounded-xl bg-white/5">
                <svg class="h-4 w-4 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18-6-6 6-6" />
                </svg>
            </span>
            <span x-show="!sidebarCollapsed" x-cloak>Collapse Sidebar</span>
            <span x-show="sidebarCollapsed" x-cloak class="erp-nav-tooltip">Expand Sidebar</span>
        </button>
    </div>
</aside>
