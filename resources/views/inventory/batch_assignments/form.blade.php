<x-app-layout>
    <x-slot name="header"><x-ui.page-header :title="$record->exists ? 'Edit Batch Assignment' : 'New Batch Assignment'" subtitle="Assign unallocated legacy stock to a real batch." /></x-slot>
    @php($lines=old('lines',$record->exists?$record->lines->map(fn($l)=>$l->toArray())->all():[['item_id'=>'','source_batch_no'=>null,'destination_batch_no'=>'','destination_expiry_date'=>'','quantity'=>'','unit_of_measure_id'=>null,'notes'=>'']]))
    <form method="POST" action="{{ $record->exists?route('batch-assignments.update',$record):route('batch-assignments.store') }}" class="mx-auto max-w-screen-xl space-y-3" data-batch-form>@csrf @if($record->exists)@method('PUT')@endif
        <div class="grid gap-3 rounded-lg border bg-white p-3 md:grid-cols-5"><div><label class="text-xs font-bold">Company</label><select name="company_id" data-company class="mt-1 h-10 w-full rounded-lg border-slate-200" required><option value="">Select company</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected($selectedCompanyId==$company->id)>{{ $company->name }}</option>@endforeach</select></div><div><label class="text-xs font-bold">Branch</label><select name="branch_id" data-branch class="mt-1 h-10 w-full rounded-lg border-slate-200 disabled:bg-slate-100" required @disabled(! $selectedCompanyId)><option value="">Select branch</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected($selectedBranchId==$b->id)>{{ $b->name }}</option>@endforeach</select></div><div><label class="text-xs font-bold">Warehouse</label><select name="warehouse_id" data-warehouse class="mt-1 h-10 w-full rounded-lg border-slate-200 disabled:bg-slate-100" required @disabled(! $selectedBranchId)><option value="">Select warehouse</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(old('warehouse_id',$record->warehouse_id)==$w->id)>{{ $w->branch?->name }} - {{ $w->name }}</option>@endforeach</select></div><div><label class="text-xs font-bold">Assignment Date</label><input type="date" name="assignment_date" value="{{ old('assignment_date',$record->assignment_date?->format('Y-m-d')) }}" class="mt-1 h-10 w-full rounded-lg border-slate-200" required></div><div><label class="text-xs font-bold">Reason</label><input name="reason" value="{{ old('reason',$record->reason) }}" class="mt-1 h-10 w-full rounded-lg border-slate-200" required></div></div>
        @if($errors->any())<div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
        <div class="overflow-x-auto rounded-lg border bg-white"><table class="min-w-full text-xs"><thead class="bg-slate-50"><tr>@foreach(['Item / Unallocated','Source','Destination Batch','Expiry','Qty','UOM','Notes',''] as $h)<th class="px-3 py-2 text-left font-black uppercase text-slate-500">{{ $h }}</th>@endforeach</tr></thead><tbody data-lines>@foreach($lines as $i=>$line)<tr data-row><td class="px-2 py-1"><select name="lines[{{ $i }}][item_id]" data-item data-current="{{ $line['item_id']??'' }}" class="w-64 rounded border-slate-200" required><option value="">Select warehouse first</option></select><p data-unallocated class="text-[11px] text-amber-700"></p></td><td class="px-2 py-1 font-bold">No Batch<input type="hidden" name="lines[{{ $i }}][source_batch_no]" value=""></td><td class="px-2 py-1"><input name="lines[{{ $i }}][destination_batch_no]" value="{{ $line['destination_batch_no']??'' }}" class="w-40 rounded border-slate-200" required></td><td class="px-2 py-1"><input type="date" name="lines[{{ $i }}][destination_expiry_date]" value="{{ $line['destination_expiry_date']??'' }}" class="w-36 rounded border-slate-200"></td><td class="px-2 py-1"><input type="number" step="0.000001" min="0.000001" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity']??'' }}" data-qty class="w-28 rounded border-slate-200" required></td><td class="px-2 py-1"><span data-uom>-</span><input type="hidden" name="lines[{{ $i }}][unit_of_measure_id]" value="{{ $line['unit_of_measure_id']??'' }}" data-uom-id></td><td class="px-2 py-1"><input name="lines[{{ $i }}][notes]" value="{{ $line['notes']??'' }}" class="w-40 rounded border-slate-200"></td><td class="px-2 py-1"><button type="button" data-remove class="font-bold text-red-600">×</button></td></tr>@endforeach</tbody></table><button type="button" data-add class="m-2 rounded border px-3 py-1.5 font-bold">Add Line</button></div>
        <div class="rounded-lg border bg-white p-3"><label class="text-xs font-bold">Notes</label><textarea name="notes" class="mt-1 w-full rounded-lg border-slate-200">{{ old('notes',$record->notes) }}</textarea></div><div class="flex justify-end gap-2"><a href="{{ route('batch-assignments.index') }}" class="rounded-lg border px-4 py-2 text-sm font-bold">Cancel</a><button name="action" value="draft" class="rounded-lg border px-4 py-2 text-sm font-bold">Save Draft</button><button name="action" value="post" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Save & Post</button></div>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-batch-form]');
            const company = form.querySelector('[data-company]');
            const branch = form.querySelector('[data-branch]');
            const warehouse = form.querySelector('[data-warehouse]');
            const body = form.querySelector('[data-lines]');
            let items = [];

            const clearLines = () => {
                items = [];
                body.querySelectorAll('[data-row]').forEach((row) => {
                    row.querySelector('[data-item]').dataset.current = '';
                    row.querySelector('[data-item]').innerHTML = '<option value="">Select warehouse first</option>';
                    row.querySelector('[data-unallocated]').textContent = '';
                    row.querySelector('[data-uom]').textContent = '-';
                    row.querySelector('[data-uom-id]').value = '';
                    row.querySelector('[name$="[destination_batch_no]"]').value = '';
                    row.querySelector('[name$="[destination_expiry_date]"]').value = '';
                    row.querySelector('[name$="[quantity]"]').value = '';
                    row.querySelector('[name$="[notes]"]').value = '';
                });
            };

            const loadBranches = async () => {
                branch.innerHTML = '<option value="">Select branch</option>';
                branch.disabled = !company.value;
                warehouse.innerHTML = '<option value="">Select warehouse</option>';
                warehouse.disabled = true;
                clearLines();
                if (!company.value) return;
                const response = await fetch(`{{ route('batch-assignments.branches') }}?company_id=${company.value}`);
                if (!response.ok) return;
                const rows = await response.json();
                rows.forEach((row) => branch.add(new Option(row.name, row.id)));
                if (rows.length === 1) {
                    branch.value = rows[0].id;
                    await loadWarehouses();
                }
            };

            const loadWarehouses = async () => {
                warehouse.innerHTML = '<option value="">Select warehouse</option>';
                warehouse.disabled = !branch.value;
                clearLines();
                if (!company.value || !branch.value) return;
                const response = await fetch(`{{ route('batch-assignments.warehouses') }}?company_id=${company.value}&branch_id=${branch.value}`);
                if (!response.ok) return;
                const rows = await response.json();
                rows.forEach((row) => warehouse.add(new Option(row.label, row.id)));
                if (rows.length === 1) {
                    warehouse.value = rows[0].id;
                    await loadItems();
                }
            };

            const sync = (row) => {
                const selected = items.find((item) => String(item.item_id) === String(row.querySelector('[data-item]').value));
                row.querySelector('[data-unallocated]').textContent = selected ? `Unallocated: ${Number(selected.unallocated_qty).toFixed(2)} ${selected.uom || ''}` : '';
                row.querySelector('[data-uom]').textContent = selected?.uom || '-';
                row.querySelector('[data-uom-id]').value = selected?.uom_id || '';
            };

            const fill = (row) => {
                const select = row.querySelector('[data-item]');
                const current = select.dataset.current || select.value;
                select.innerHTML = '<option value="">Select eligible item</option>' + items.map((item) => `<option value="${item.item_id}" ${String(item.item_id) === String(current) ? 'selected' : ''}>${item.sku} - ${item.name} (No Batch ${Number(item.unallocated_qty).toFixed(2)} ${item.uom || ''})</option>`).join('');
                sync(row);
            };

            const loadItems = async () => {
                items = warehouse.value ? await fetch(`{{ route('batch-assignments.eligible-items') }}?company_id=${company.value}&branch_id=${branch.value}&warehouse_id=${warehouse.value}`).then((response) => response.json()) : [];
                body.querySelectorAll('[data-row]').forEach(fill);
            };

            company.addEventListener('change', loadBranches);
            branch.addEventListener('change', loadWarehouses);
            warehouse.addEventListener('change', () => { clearLines(); loadItems(); });
            body.addEventListener('change', (event) => { if (event.target.matches('[data-item]')) sync(event.target.closest('[data-row]')); });
            form.querySelector('[data-add]').addEventListener('click', () => {
                const row = body.firstElementChild.cloneNode(true);
                const index = body.children.length;
                row.querySelectorAll('[name]').forEach((element) => {
                    element.name = element.name.replace(/lines\[\d+\]/, `lines[${index}]`);
                    if (!element.matches('[type=hidden]')) element.value = '';
                });
                row.querySelector('[data-item]').dataset.current = '';
                body.append(row);
                fill(row);
            });
            body.addEventListener('click', (event) => { if (event.target.matches('[data-remove]') && body.children.length > 1) event.target.closest('tr').remove(); });
            loadItems();
        });
    </script>
</x-app-layout>
