<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 font-sans antialiased text-slate-900">
        <div x-data="{ sidebarOpen: false, sidebarCollapsed: false }" class="min-h-screen">
            <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" @click="sidebarOpen = false"></div>

            @include('layouts.navigation')

            <div class="transition-all duration-300" :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 shadow-sm backdrop-blur">
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
                                    <input type="search" placeholder="Search menu, document, item..." class="block w-full rounded-xl border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm font-medium text-slate-700 shadow-sm focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                </label>
                            </div>

                            <x-dropdown align="right" width="64">
                                <x-slot name="trigger">
                                    <button class="hidden rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm shadow-emerald-900/10 hover:bg-emerald-700 sm:inline-flex">
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

                            <button type="button" class="relative grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50 hover:text-slate-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9" /></svg>
                                <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                            </button>

                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center gap-3 rounded-full border border-slate-200 bg-white py-1 pl-1 pr-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                        <span class="grid h-8 w-8 place-items-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">{{ str(Auth::user()->name)->substr(0, 1)->upper() }}</span>
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
