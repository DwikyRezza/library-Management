@props([
    'book',
    'compact' => false,
])

@php
    $asset = $book->digitalAsset;
    $hasActiveLoan = (bool) ($book->getAttribute('has_active_digital_loan') ?? false);
    $lastReadPage = max(1, (int) ($book->getAttribute('active_loan_last_read_page') ?? 1));
    $readLabel = $lastReadPage > 1 ? "Lanjutkan Membaca (Hal. {$lastReadPage})" : 'Read';
    $isInBooklist = (bool) ($book->getAttribute('is_in_booklist') ?? false);
    $modalName = 'book-description-'.$book->id;
    $buttonClass = $compact ? 'btn-ghost px-2.5 py-2 text-xs' : 'btn-secondary flex-1 px-3';
@endphp

<div class="{{ $compact ? 'mt-3 flex flex-wrap gap-2' : 'mt-4 grid grid-cols-2 gap-2' }}">
    <button
        type="button"
        class="{{ $buttonClass }}"
        @click="$dispatch('open-modal', '{{ $modalName }}')"
    >
        Description
    </button>

    @auth('member')
        <form method="POST" action="{{ $isInBooklist ? route('member.booklist.destroy', $book) : route('member.booklist.store', $book) }}" class="{{ $compact ? '' : 'flex' }}">
            @csrf
            @if ($isInBooklist)
                @method('DELETE')
            @endif
            <button type="submit" class="{{ $buttonClass }} {{ $compact ? '' : 'w-full' }}">
                {{ $isInBooklist ? 'Saved' : 'Booklist' }}
            </button>
        </form>
    @else
        <a href="{{ route('member.login') }}" class="{{ $buttonClass }}">Booklist</a>
    @endauth

    @if ($asset?->isReady())
        @if ($hasActiveLoan)
            <a
                href="{{ route('member.reader.open', $book) }}"
                class="{{ $compact ? 'btn-primary px-3 py-2 text-xs' : 'btn-primary col-span-2 w-full' }}"
            >
                {{ $readLabel }}
            </a>
        @elseif ($book->available_copies > 0)
            @auth('member')
                <form method="POST" action="{{ route('member.digital-loans.store', $book) }}" class="{{ $compact ? '' : 'col-span-2' }}">
                    @csrf
                    <button type="submit" class="btn-primary w-full">Borrow</button>
                </form>
            @else
                <a href="{{ route('member.login') }}" class="{{ $compact ? 'btn-primary px-3 py-2 text-xs' : 'btn-primary col-span-2 w-full' }}">
                    Borrow
                </a>
            @endauth
        @else
            <span class="{{ $compact ? 'inline-flex items-center text-xs font-semibold text-slate-400' : 'col-span-2 inline-flex w-full items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400' }}">
                Semua copy sedang dipinjam
            </span>
        @endif
    @elseif ($asset?->status === \App\Models\DigitalBookAsset::STATUS_PROCESSING)
        <span class="{{ $compact ? 'text-xs font-bold text-amber-700 dark:text-amber-200' : 'col-span-2 inline-flex w-full items-center justify-center rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200' }}">
            Sedang diproses
        </span>
    @elseif ($asset?->status === \App\Models\DigitalBookAsset::STATUS_FAILED)
        <span class="{{ $compact ? 'text-xs font-bold text-rose-700 dark:text-rose-200' : 'col-span-2 inline-flex w-full items-center justify-center rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-200' }}">
            Belum dapat dibaca
        </span>
    @else
        <span class="{{ $compact ? 'text-xs font-semibold text-slate-400' : 'col-span-2 inline-flex w-full items-center justify-center rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400' }}">
            Versi digital belum tersedia
        </span>
    @endif
</div>

<x-book-description-modal :book="$book" :name="$modalName" />
