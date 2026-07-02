<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 font-sans antialiased text-slate-900">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false"></div>

            @include('layouts.navigation')

            <div class="lg:pl-72">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                    <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden" @click="sidebarOpen = true">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="min-w-0">
                                @isset($header)
                                    {{ $header }}
                                @else
                                    <h1 class="truncate text-lg font-semibold text-slate-900">Linvy ERP</h1>
                                @endisset
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-dropdown align="right" width="64">
                                <x-slot name="trigger">
                                    <button class="hidden rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 sm:inline-flex">
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
                                    @endif
                                </x-slot>
                            </x-dropdown>

                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center gap-3 rounded-full border border-slate-200 bg-white py-1 pl-1 pr-3 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                                        <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">{{ str(Auth::user()->name)->substr(0, 1)->upper() }}</span>
                                        <span class="hidden sm:block">{{ Auth::user()->name }}</span>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="px-4 py-2 text-xs text-slate-500">{{ Auth::user()->role?->name ?? 'No Role' }}</div>
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

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
