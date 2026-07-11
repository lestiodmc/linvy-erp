@props(['status', 'message' => null])
<div {{ $attributes->class('enterprise-status-banner theme-card flex items-center gap-3 rounded-lg px-3 py-2') }}><x-ui.status-badge :status="$status" /><span class="text-xs theme-muted">{{ $message ?: str($status)->replace('_', ' ')->title() }}</span></div>
