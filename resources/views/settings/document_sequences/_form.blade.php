@php
    $knownDateFormats = $dateFormatOptions ?? ['YYYYMM', 'YYYY', 'YYMM', 'YYYYMMDD'];
    $storedDateFormat = old('date_format', $record?->date_format ?? 'YYYYMM');
    $dateFormatChoice = in_array($storedDateFormat, $knownDateFormats, true) ? $storedDateFormat : 'CUSTOM';
    $customDateFormat = old('custom_date_format', $dateFormatChoice === 'CUSTOM' ? $storedDateFormat : '');
    $currentCounter = $record?->current_counter ?? 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="truncate text-xl font-black text-slate-950">{{ $record ? 'Edit '.$title : 'New '.$title }}</h1>
            <p class="mt-0.5 text-sm font-medium text-slate-500">{{ $record ? 'Update document numbering rule' : 'Create document numbering rule' }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl">
        <form method="POST" action="{{ $action }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-document-sequence-form>
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_320px]">
                <div class="space-y-8">
                    <section>
                        <div class="mb-4">
                            <h4 class="text-sm font-black uppercase tracking-wide text-slate-700">General</h4>
                            <div class="mt-2 h-px bg-slate-100"></div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="code" class="block text-sm font-bold text-slate-700">Code</label>
                                <input id="code" name="code" type="text" value="{{ old('code', $record?->code) }}" class="mt-1 block w-full rounded-xl border-slate-300 uppercase shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                @error('code')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="name" class="block text-sm font-bold text-slate-700">Name</label>
                                <input id="name" name="name" type="text" value="{{ old('name', $record?->name) }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                @error('name')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-bold text-slate-700">Description</label>
                                <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">{{ old('description', $record?->description) }}</textarea>
                                @error('description')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section>
                        <div class="mb-4">
                            <h4 class="text-sm font-black uppercase tracking-wide text-slate-700">Format</h4>
                            <div class="mt-2 h-px bg-slate-100"></div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="prefix" class="block text-sm font-bold text-slate-700">Prefix</label>
                                <input id="prefix" name="prefix" type="text" value="{{ old('prefix', $record?->prefix) }}" class="mt-1 block w-full rounded-xl border-slate-300 uppercase shadow-sm focus:border-emerald-600 focus:ring-emerald-600" data-preview-source>
                                @error('prefix')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="separator" class="block text-sm font-bold text-slate-700">Separator</label>
                                <input id="separator" name="separator" type="text" maxlength="3" value="{{ old('separator', $record?->separator ?? '-') }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" data-preview-source>
                                @error('separator')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="date_format" class="block text-sm font-bold text-slate-700">Date Format</label>
                                <select id="date_format" name="date_format" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" data-preview-source>
                                    @foreach($knownDateFormats as $format)
                                        <option value="{{ $format }}" @selected($dateFormatChoice === $format)>{{ $format }}</option>
                                    @endforeach
                                    <option value="CUSTOM" @selected($dateFormatChoice === 'CUSTOM')>CUSTOM</option>
                                </select>
                                @error('date_format')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div data-custom-date-format-wrapper class="{{ $dateFormatChoice === 'CUSTOM' ? '' : 'hidden' }}">
                                <label for="custom_date_format" class="block text-sm font-bold text-slate-700">Custom Date Format</label>
                                <input id="custom_date_format" name="custom_date_format" type="text" value="{{ $customDateFormat }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" data-preview-source>
                                @error('custom_date_format')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="digits" class="block text-sm font-bold text-slate-700">Digits</label>
                                <input id="digits" name="digits" type="number" min="1" max="10" step="1" value="{{ old('digits', $record?->digits ?? 5) }}" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" data-preview-source>
                                @error('digits')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="reset_type" class="block text-sm font-bold text-slate-700">Reset Type</label>
                                <select id="reset_type" name="reset_type" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600" data-preview-source>
                                    <option value="monthly" @selected(old('reset_type', $record?->reset_type ?? 'monthly') === 'monthly')>Monthly</option>
                                    <option value="yearly" @selected(old('reset_type', $record?->reset_type) === 'yearly')>Yearly</option>
                                    <option value="never" @selected(old('reset_type', $record?->reset_type) === 'never')>Never</option>
                                </select>
                                <p class="mt-1 text-xs font-medium text-slate-500" data-reset-help>{{ $resetTypeHelp[old('reset_type', $record?->reset_type ?? 'monthly')] ?? $resetTypeHelp['monthly'] }}</p>
                                @error('reset_type')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section>
                        <div class="mb-4">
                            <h4 class="text-sm font-black uppercase tracking-wide text-slate-700">Scope</h4>
                            <div class="mt-2 h-px bg-slate-100"></div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="company_id" class="block text-sm font-bold text-slate-700">Company</label>
                                <select id="company_id" name="company_id" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    <option value="">Global / All Company</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" @selected((string) old('company_id', $record?->company_id) === (string) $company->id)>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                                @error('company_id')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="branch_id" class="block text-sm font-bold text-slate-700">Branch</label>
                                <select id="branch_id" name="branch_id" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                                    <option value="">Global / All Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" data-company-id="{{ $branch->company_id }}" @selected((string) old('branch_id', $record?->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @error('branch_id')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mt-7 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800">
                                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $record?->is_active ?? true)) class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-600">
                                    Active
                                </label>
                                @error('is_active')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Next Number Preview</p>
                        <p class="mt-3 break-all text-2xl font-black text-slate-950" data-preview-output>{{ $record?->preview_number ?? 'PO-'.now()->format('Ym').'-00001' }}</p>
                        <button type="button" class="mt-4 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" data-refresh-preview>Generate Sample / Refresh Preview</button>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <label for="current_counter" class="block text-xs font-black uppercase tracking-wide text-slate-500">Current Counter</label>
                        <input id="current_counter" type="text" value="{{ $currentCounter }}" readonly class="mt-3 block w-full rounded-xl border-slate-200 bg-slate-50 text-lg font-black text-slate-900 shadow-sm">
                    </div>
                </aside>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
                <a href="{{ route($route.'.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="button-primary">Save</button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-document-sequence-form]');
            if (!form) return;

            const resetHelp = @json($resetTypeHelp);
            const counter = Number(@json($currentCounter)) || 0;
            const previewOutput = form.querySelector('[data-preview-output]');
            const customWrapper = form.querySelector('[data-custom-date-format-wrapper]');
            const resetHelpOutput = form.querySelector('[data-reset-help]');
            const companySelect = form.querySelector('#company_id');
            const branchSelect = form.querySelector('#branch_id');

            const value = (selector, fallback = '') => form.querySelector(selector)?.value ?? fallback;

            const formattedDate = (format) => {
                const now = new Date();
                const yyyy = String(now.getFullYear());
                const yy = yyyy.slice(-2);
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                const dd = String(now.getDate()).padStart(2, '0');

                return (format || 'YYYYMM')
                    .replaceAll('YYYY', yyyy)
                    .replaceAll('YY', yy)
                    .replaceAll('MM', mm)
                    .replaceAll('DD', dd);
            };

            const refreshPreview = () => {
                const dateChoice = value('#date_format', 'YYYYMM');
                const dateFormat = dateChoice === 'CUSTOM' ? value('#custom_date_format', 'YYYYMM') : dateChoice;
                const digits = Math.min(10, Math.max(1, Number(value('#digits', 5)) || 5));
                const nextNumber = String(counter + 1).padStart(digits, '0');
                const separator = value('#separator', '-');
                const parts = [value('#prefix', 'DOC').toUpperCase(), formattedDate(dateFormat), nextNumber].filter(Boolean);

                previewOutput.textContent = parts.join(separator);
                customWrapper.classList.toggle('hidden', dateChoice !== 'CUSTOM');
                resetHelpOutput.textContent = resetHelp[value('#reset_type', 'monthly')] || resetHelp.monthly;
            };

            const filterBranches = () => {
                const companyId = companySelect.value;

                [...branchSelect.options].forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = companyId !== '' && option.dataset.companyId !== companyId;
                });

                if (branchSelect.selectedOptions[0]?.hidden) {
                    branchSelect.value = '';
                }
            };

            form.querySelectorAll('[data-preview-source]').forEach((input) => {
                input.addEventListener('input', refreshPreview);
                input.addEventListener('change', refreshPreview);
            });

            form.querySelector('[data-refresh-preview]')?.addEventListener('click', refreshPreview);
            companySelect?.addEventListener('change', filterBranches);

            filterBranches();
            refreshPreview();
        })();
    </script>
</x-app-layout>
