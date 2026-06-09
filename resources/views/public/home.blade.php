@extends('layouts.app')

@section('title', 'LibraFlow - Discover your next book')

@section('content')
<section class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-20">
    <div>
        <p class="section-kicker">Campus library catalog</p>
        <h1 class="mt-4 text-4xl font-black text-slate-950 sm:text-5xl dark:text-white">Find the right book and keep learning moving.</h1>
        <p class="mt-5 max-w-xl text-base leading-8 text-slate-600 dark:text-slate-300">Search the live catalog, check copy availability, and register for library membership from any device.</p>
        <form action="{{ route('books.search') }}" method="GET" class="panel mt-8 flex max-w-xl flex-col gap-2 p-2 sm:flex-row">
            <input class="input border-0 shadow-none" name="q" placeholder="Search title, author, ISBN, or category">
            <button class="btn-primary shrink-0">Search catalog</button>
        </form>
        <div class="mt-8 grid max-w-xl grid-cols-3 gap-3">
            <div class="rounded-lg border border-slate-200 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                <strong class="block text-2xl text-slate-950 dark:text-white">{{ number_format($bookCount) }}+</strong>
                <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Curated titles</span>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                <strong class="block text-2xl text-slate-950 dark:text-white">{{ number_format($availableCount) }}</strong>
                <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Copies available</span>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                <strong class="block text-2xl text-slate-950 dark:text-white">{{ number_format($categoryCount) }}</strong>
                <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Knowledge areas</span>
            </div>
        </div>
    </div>

    <div class="panel p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="section-kicker">Shelf preview</p>
                <h2 class="mt-2 text-xl font-black text-slate-950 dark:text-white">Fresh from the catalog</h2>
            </div>
            <a href="{{ route('books.search') }}" class="subtle-link text-sm">Browse all</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4">
            @forelse ($featuredBooks->take(4) as $book)
                <article class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-white/10 dark:bg-white/5">
                    @if($book->cover_image)
                        <img src="{{ route('books.cover', $book) }}" class="mx-auto h-28 w-20 rounded-lg object-cover shadow-sm" alt="{{ $book->title }}">
                    @else
                        <div class="book-cover mx-auto">
                            <span class="text-[9px] font-black uppercase text-blue-500 dark:text-blue-200">Book</span>
                            <span class="line-clamp-4 text-xs font-black leading-tight">{{ $book->title }}</span>
                            <span class="truncate text-[10px] text-blue-700/70 dark:text-blue-100/70">{{ $book->author }}</span>
                        </div>
                    @endif
                    <p class="mt-3 line-clamp-2 text-sm font-bold text-slate-900 dark:text-white">{{ $book->title }}</p>
                    <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $book->category->name }}</p>
                    <x-book-reader-action :book="$book" compact />
                </article>
            @empty
                <div class="col-span-2 rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
                    Catalog is being prepared.
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker">Featured books</p>
            <h2 class="mt-1 text-3xl font-black text-slate-950 dark:text-white">Recommended from the shelves</h2>
        </div>
        <a href="{{ route('books.search') }}" class="btn-secondary self-start sm:self-auto">Browse all</a>
    </div>

    @if ($featuredBooks->isEmpty())
        <div class="mt-8"><x-empty-state title="Catalog is being prepared" message="Books will appear here as soon as the library team adds them." /></div>
    @else
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($featuredBooks as $book)
                <article class="panel p-5 transition hover:border-blue-200 hover:bg-white dark:hover:border-blue-400/20 dark:hover:bg-slate-900/90">
                    <div class="flex items-start gap-4">
                        @if($book->cover_image)
                            <img src="{{ route('books.cover', $book) }}" class="h-28 w-20 shrink-0 rounded-lg object-cover shadow-sm" alt="{{ $book->title }}">
                        @else
                            <div class="book-cover">
                                <span class="text-[9px] font-black uppercase text-blue-500 dark:text-blue-200">Book</span>
                                <span class="line-clamp-4 text-xs font-black leading-tight">{{ $book->title }}</span>
                                <span class="truncate text-[10px] text-blue-700/70 dark:text-blue-100/70">{{ $book->author }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <x-badge :status="$book->available_copies > 0 ? 'available' : 'unavailable'" />
                            <h3 class="mt-3 line-clamp-2 font-bold text-slate-950 dark:text-white">{{ $book->title }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $book->author }}</p>
                            <p class="mt-3 text-xs font-semibold text-blue-700 dark:text-blue-300">{{ $book->category->name }}</p>
                            <x-book-reader-action :book="$book" compact />
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="panel flex flex-col gap-5 bg-blue-50/70 p-6 dark:bg-blue-500/10 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="section-kicker">Ready to borrow?</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950 dark:text-white">Join your campus library community.</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Registration takes only a few minutes and is reviewed by a librarian.</p>
        </div>
        <a href="{{ route('member.register') }}" class="btn-primary self-start sm:self-auto">Register as member</a>
    </div>
</section>
@endsection
