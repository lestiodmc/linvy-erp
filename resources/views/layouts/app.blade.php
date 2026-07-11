<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="linvy-emerald">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <script>
            (() => {
                const allowed = ['linvy-emerald', 'ocean-blue', 'royal-purple', 'amber-gold', 'rose-red', 'slate-dark'];
                try {
                    const saved = localStorage.getItem('linvy_theme');
                    document.documentElement.dataset.theme = allowed.includes(saved) ? saved : 'linvy-emerald';
                } catch (_) {
                    document.documentElement.dataset.theme = 'linvy-emerald';
                }
            })();
        </script>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false, sidebarCollapsed: false }" class="min-h-screen">
            <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false"></div>

            @include('layouts.navigation')

            <div class="transition-all duration-300" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
                <header class="theme-surface sticky top-0 z-30 border-b shadow-sm">
                    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 xl:px-8">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden" @click="sidebarOpen = true">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="min-w-0 flex-1">
                                @isset($header)
                                    {{ $header }}
                                @else
                                    <h1 class="truncate text-lg font-bold text-slate-900">Linvy ERP</h1>
                                @endisset
                            </div>
                        </div>

                        <div class="flex flex-1 items-center justify-end gap-3">
                            <div class="hidden w-full max-w-sm lg:block">
                                <label class="relative block">
                                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" /></svg>
                                    </span>
                                    <input type="search" placeholder="Search menu, document, item..." class="theme-surface-secondary block w-full rounded-xl py-2 pl-9 pr-3 text-sm font-medium shadow-sm">
                                </label>
                            </div>

                            <x-dropdown align="right" width="64">
                                <x-slot name="trigger">
                                    <button class="theme-primary theme-focus hidden rounded-lg px-4 py-2 text-sm font-bold shadow-sm sm:inline-flex">
                                        Quick Action
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    @if(Auth::user()?->canAccessModule('purchase') && \App\Support\ModuleManager::enabled('purchase'))
                                        <x-dropdown-link :href="route('purchase-orders.create')">New Purchase Order</x-dropdown-link>
                                        <x-dropdown-link :href="route('receivings.create')">New Receiving</x-dropdown-link>
                                    @endif
                                    @if(Auth::user()?->canAccessModule('sales') && \App\Support\ModuleManager::enabled('sales'))
                                        <x-dropdown-link :href="route('sales-orders.create')">New Sales Order</x-dropdown-link>
                                        <x-dropdown-link :href="route('delivery-orders.create')">New Delivery Order</x-dropdown-link>
                                    @endif
                                    @if(Auth::user()?->canAccessModule('inventory') && \App\Support\ModuleManager::enabled('inventory'))
                                        <x-dropdown-link :href="route('warehouse-transfers.create')">New Transfer</x-dropdown-link>
                                        <x-dropdown-link :href="route('stock-adjustments.create')">New Adjustment</x-dropdown-link>
                                        <x-dropdown-link :href="route('batch-assignments.create')">New Batch Assignment</x-dropdown-link>
                                    @endif
                                    @if(Auth::user()?->canAccessModule('production') && \App\Support\ModuleManager::enabled('production'))
                                        <x-dropdown-link :href="route('productions.create')">New Repacking Order</x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>

                            @php
                                $themeOptions = [
                                    ['id' => 'linvy-emerald', 'name' => 'Linvy Emerald', 'color' => '#059669'],
                                    ['id' => 'ocean-blue', 'name' => 'Ocean Blue', 'color' => '#2563eb'],
                                    ['id' => 'royal-purple', 'name' => 'Royal Purple', 'color' => '#7c3aed'],
                                    ['id' => 'amber-gold', 'name' => 'Amber Gold', 'color' => '#b45309'],
                                    ['id' => 'rose-red', 'name' => 'Rose Red', 'color' => '#be123c'],
                                    ['id' => 'slate-dark', 'name' => 'Slate Dark', 'color' => '#111827'],
                                ];
                            @endphp
                            <div class="relative" x-data="{ open: false, selected: document.documentElement.dataset.theme }" @click.outside="open = false" @keydown.escape.window="open = false">
                                <button type="button" class="theme-surface theme-focus grid h-10 w-10 place-items-center rounded-xl border shadow-sm" @click="open = !open" :aria-expanded="open" aria-haspopup="listbox" aria-label="Choose application theme">
                                    <svg class="h-5 w-5 theme-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a9 9 0 1 0 0 18h1.2a1.8 1.8 0 0 0 0-3.6h-.6a1.5 1.5 0 0 1 0-3H15a6 6 0 0 0 0-12h-3Zm-4 6h.01M11 6h.01M6 13h.01" /></svg>
                                </button>
                                <div x-show="open" x-cloak x-transition class="theme-dropdown absolute right-0 z-50 mt-2 w-56 rounded-xl p-1.5 shadow-xl" role="listbox" aria-label="Application themes">
                                    @foreach($themeOptions as $theme)
                                        <button type="button" class="theme-option theme-focus flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm" @click="selected = window.LinvyTheme.apply('{{ $theme['id'] }}'); open = false" role="option" :aria-selected="selected === '{{ $theme['id'] }}'">
                                            <span class="h-3.5 w-3.5 rounded-full ring-1 ring-black/10" style="background: {{ $theme['color'] }}"></span>
                                            <span class="flex-1 font-semibold">{{ $theme['name'] }}</span>
                                            <svg x-show="selected === '{{ $theme['id'] }}'" class="h-4 w-4 theme-link" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" /></svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <button type="button" class="theme-surface theme-focus relative grid h-10 w-10 place-items-center rounded-xl border shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9" /></svg>
                                <span class="absolute right-2 top-2 h-2 w-2 rounded-full theme-primary ring-2 ring-white"></span>
                            </button>

                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="theme-surface theme-focus flex items-center gap-3 rounded-full border py-1 pl-1 pr-3 text-sm font-semibold shadow-sm">
                                        <span class="theme-primary-soft grid h-8 w-8 place-items-center rounded-full text-xs font-bold">{{ str(Auth::user()->name)->substr(0, 1)->upper() }}</span>
                                        <span class="hidden sm:block">{{ Auth::user()->name }}</span>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="border-b border-slate-100 px-4 py-3">
                                        <div class="text-sm font-bold text-slate-900">{{ Auth::user()->name }}</div>
                                        <div class="mt-0.5 text-xs font-medium text-slate-500">Role: {{ Auth::user()->role?->name ?? 'No Role' }}</div>
                                    </div>
                                    <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                            Log Out
                                        </x-dropdown-link>
                                    </form>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                </header>

                <main class="px-4 py-6 sm:px-6 xl:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
