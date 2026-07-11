<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$record->number ?: 'Batch Assignment'"
            subtitle="Batch assignment audit document."
        />
    </x-slot>

    @php
        $formatQty = fn ($value) => number_format((float) ($value ?? 0), 2);
        $formatDate = fn ($value, $withTime = false) => $value
            ? \Illuminate\Support\Carbon::parse($value)->format($withTime ? 'd M Y H:i' : 'd M Y')
            : '-';
        $previewLabels = [
            'warehouse_before' => 'Warehouse Before',
            'warehouse_after' => 'Warehouse After',
            'batch_before' => 'Batch Before',
            'batch_after' => 'Batch After',
            'difference_before' => 'Difference Before',
            'difference_after' => 'Difference After',
        ];
    @endphp

    <div class="mx-auto max-w-screen-xl space-y-3">
        @if(session('status'))
            <div class="rounded-lg bg-emerald-50 p-3 text-sm font-bold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <dl class="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="font-bold text-slate-500">Document No</dt><dd class="font-semibold text-slate-900">{{ $record->number ?: '-' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Assignment Date</dt><dd>{{ $formatDate($record->assignment_date) }}</dd></div>
                <div><dt class="font-bold text-slate-500">Company</dt><dd>{{ $record->company?->name ?: '-' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Branch</dt><dd>{{ $record->branch?->name ?: '-' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Warehouse</dt><dd>{{ $record->warehouse?->name ?: '-' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Status</dt><dd><x-ui.status-badge :status="$record->status ?: 'draft'" /></dd></div>
                <div><dt class="font-bold text-slate-500">Created By</dt><dd>{{ $record->createdBy?->name ?: '-' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Posted By</dt><dd>{{ $record->postedBy?->name ?: '-' }}</dd></div>
                <div><dt class="font-bold text-slate-500">Posted At</dt><dd>{{ $formatDate($record->posted_at, true) }}</dd></div>
                <div class="sm:col-span-2"><dt class="font-bold text-slate-500">Reason</dt><dd>{{ $record->reason ?: '-' }}</dd></div>
                <div class="sm:col-span-2"><dt class="font-bold text-slate-500">Notes</dt><dd class="whitespace-pre-line">{{ $record->notes ?: '-' }}</dd></div>
            </dl>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
            @foreach($previewLabels as $key => $label)
                @php
                    $value = (float) ($preview[$key] ?? 0);
                    $isDifference = str_contains($key, 'difference');
                @endphp
                <div class="rounded-lg border border-slate-200 bg-white p-3">
                    <p class="text-[10px] font-black uppercase text-slate-500">{{ $label }}</p>
                    <p class="text-lg font-black {{ $isDifference && abs($value) > 0.000001 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ $formatQty($value) }}
                    </p>
                </div>
            @endforeach
        </div>

        <x-ui.data-table class="rounded-lg shadow-none">
            <x-slot:head>
                <tr>
                    @foreach(['Item', 'Source Batch', 'Destination Batch', 'Destination Expiry', 'Quantity', 'UOM', 'Line Notes'] as $heading)
                        <th class="px-3 py-2 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">{{ $heading }}</th>
                    @endforeach
                </tr>
            </x-slot:head>

            @forelse($record->lines as $line)
                <tr class="text-xs">
                    <td class="px-3 py-2 font-bold text-slate-900">{{ trim(($line->item?->sku ? $line->item->sku.' - ' : '').($line->item?->name ?: 'Unknown Item')) }}</td>
                    <td class="px-3 py-2">{{ filled($line->source_batch_no) ? $line->source_batch_no : 'No Batch' }}</td>
                    <td class="px-3 py-2 font-bold">{{ $line->destination_batch_no ?: '-' }}</td>
                    <td class="px-3 py-2">{{ $formatDate($line->destination_expiry_date) }}</td>
                    <td class="px-3 py-2 text-right font-semibold">{{ $formatQty($line->quantity) }}</td>
                    <td class="px-3 py-2">{{ $line->unit?->code ?: $line->item?->baseUnit?->code ?: '-' }}</td>
                    <td class="px-3 py-2">{{ $line->notes ?: '-' }}</td>
                </tr>
            @empty
                <x-ui.empty-state colspan="7" message="No assignment lines found." />
            @endforelse
        </x-ui.data-table>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('batch-assignments.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700">Back</a>
            @if($record->isDraft())
                <a href="{{ route('batch-assignments.edit', $record) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700">Edit</a>
                <form method="POST" action="{{ route('batch-assignments.cancel', $record) }}">
                    @csrf
                    <button class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700">Cancel</button>
                </form>
                <form method="POST" action="{{ route('batch-assignments.post', $record) }}">
                    @csrf
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Post</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
