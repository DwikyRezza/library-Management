@props([
    'book',
    'compact' => false,
])

@php
    $asset = $book->digitalAsset;
@endphp

@if ($asset?->isReady())
    <a
        href="{{ route('member.reader.open', $book) }}"
        class="{{ $compact ? 'mt-3 inline-flex text-sm font-bold text-emerald-700 hover:text-emerald-900 dark:text-emerald-200 dark:hover:text-emerald-100' : 'btn-primary mt-4 w-full' }}"
    >
        Read
    </a>
@elseif ($asset?->status === \App\Models\DigitalBookAsset::STATUS_PROCESSING)
    <span class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
        Sedang diproses
    </span>
@elseif ($asset?->status === \App\Models\DigitalBookAsset::STATUS_FAILED)
    <span class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-200">
        Belum dapat dibaca
    </span>
@else
    <span class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
        Versi digital belum tersedia
    </span>
@endif
