@props([
    'name',
    'title' => 'Confirm action',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
])

<x-modal :name="$name" focusable>
    <div class="p-6">
        <h2 class="text-lg font-black text-slate-950">{{ $title }}</h2>

        <div class="mt-2 text-sm font-medium text-slate-600">
            {{ $slot }}
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <button type="button" x-on:click="$dispatch('close')" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                {{ $cancelText }}
            </button>

            @isset($confirmAction)
                {{ $confirmAction }}
            @else
                <button type="button" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                    {{ $confirmText }}
                </button>
            @endisset
        </div>
    </div>
</x-modal>
