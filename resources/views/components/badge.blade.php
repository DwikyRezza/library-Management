@props(['status'])
@php
    $normalized = strtolower((string) $status);
    $styles = match ($normalized) {
        'available', 'approved', 'returned' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'borrowed' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        'pending', 'maintenance' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
        'overdue', 'rejected', 'lost', 'unavailable' => 'bg-red-500/10 text-red-400 border border-red-500/20',
        default => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
    };
@endphp
<span {{ $attributes->class(["inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold capitalize {$styles}"]) }}>
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
