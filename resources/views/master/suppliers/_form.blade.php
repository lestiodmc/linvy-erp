<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record ? 'Edit '.$title : 'New '.$title }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">Manage supplier profile, contact, purchasing defaults, and purchase status.</p>
        </div>
    </x-slot>

    @php
        $sections = [
            'General' => ['code', 'name', 'supplier_group', 'supplier_type', 'tax_number', 'contact_person'],
            'Contact' => ['phone', 'mobile', 'email', 'website'],
            'Address' => ['address', 'city', 'province', 'country', 'postal_code'],
            'Purchasing' => ['default_currency_id', 'payment_term_id', 'lead_time_days', 'default_tax_id', 'ap_account_id'],
            'Status' => ['blocked_purchase', 'is_active'],
        ];
    @endphp

    <div class="mx-auto max-w-6xl">
        <form method="POST" action="{{ $action }}" class="enterprise-form overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-black text-slate-950">Supplier Information</h3>
                <p class="mt-1 text-sm text-slate-500">Fill the master data used by purchasing and future accounting integration.</p>
            </div>

            <div class="space-y-8 p-6">
                @foreach($sections as $sectionTitle => $sectionFields)
                    <section>
                        <div class="mb-4">
                            <h4 class="text-sm font-black uppercase tracking-wide text-slate-700">{{ $sectionTitle }}</h4>
                            <div class="mt-2 h-px bg-slate-100"></div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            @foreach($sectionFields as $name)
                                @include('shared.resources.field', ['name' => $name, 'field' => $fields[$name]])
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <a href="{{ route($route.'.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="button-primary">Save</button>
            </div>
        </form>
    </div>
</x-app-layout>
