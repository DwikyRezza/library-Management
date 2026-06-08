@props(['status'])
@php
    $normalized = strtolower((string) $status);
    $styles = match ($normalized) {
        'available', 'approved', 'returned' => 'border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-200',
        'borrowed' => 'border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-400/20 dark:bg-blue-400/10 dark:text-blue-200',
        'pending', 'maintenance' => 'border border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200',
        'overdue', 'rejected', 'lost', 'unavailable' => 'border border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-200',
        default => 'border border-slate-200 bg-slate-50 text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300',
    };
@endphp
<span {{ $attributes->class(["inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold capitalize {$styles}"]) }}>
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
