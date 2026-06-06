@props(['status'])
@php
    $normalized = strtolower((string) $status);
    $styles = match ($normalized) {
        'available', 'approved', 'returned' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'borrowed' => 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'pending', 'maintenance' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'overdue', 'rejected', 'lost', 'unavailable' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
        default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    };
@endphp
<span {{ $attributes->class(["inline-flex rounded-full px-2.5 py-1 text-xs font-bold capitalize {$styles}"]) }}>
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
