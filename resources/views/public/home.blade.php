@extends('layouts.app')

@section('title', 'LibraFlow - Discover your next book')

@section('content')
<section class="relative overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.18),_transparent_40%),radial-gradient(circle_at_80%_30%,_rgba(16,185,129,0.14),_transparent_35%)]"></div>
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-28">
        <div>
            <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-sm font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Campus knowledge, beautifully organized</span>
            <h1 class="mt-6 text-4xl font-black tracking-tight sm:text-6xl">Find the right book. <span class="text-indigo-600 dark:text-indigo-400">Keep learning moving.</span></h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600 dark:text-slate-300">Search the live catalog, check copy availability, and register for library membership from any device.</p>
            <form action="{{ route('books.search') }}" method="GET" class="panel mt-8 flex max-w-xl gap-2 p-2">
                <input class="input border-0 shadow-none" name="q" placeholder="Search title, author, ISBN, or category">
                <button class="btn-primary shrink-0">Search catalog</button>
            </form>
            <div class="mt-8 flex flex-wrap gap-8">
                <div><strong class="block text-2xl">{{ number_format($bookCount) }}+</strong><span class="text-sm text-slate-500 dark:text-slate-400">Curated titles</span></div>
                <div><strong class="block text-2xl">{{ number_format($availableCount) }}</strong><span class="text-sm text-slate-500 dark:text-slate-400">Copies available</span></div>
                <div><strong class="block text-2xl">{{ number_format($categoryCount) }}</strong><span class="text-sm text-slate-500 dark:text-slate-400">Knowledge areas</span></div>
            </div>
        </div>
        <div class="panel rotate-2 bg-gradient-to-br from-indigo-600 to-blue-700 p-8 text-white shadow-2xl shadow-indigo-500/20">
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-indigo-200">LibraFlow spotlight</p>
            <h2 class="mt-4 text-3xl font-black">A calmer way to navigate campus knowledge.</h2>
            <div class="mt-8 grid grid-cols-2 gap-4">
                @foreach (['Live availability', 'Fast discovery', 'Simple registration', 'Responsive access'] as $feature)
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 text-sm font-semibold backdrop-blur">{{ $feature }}</div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex items-end justify-between gap-4">
        <div><p class="font-bold text-indigo-600 dark:text-indigo-400">Fresh from the shelves</p><h2 class="mt-1 text-3xl font-black">Featured books</h2></div>
        <a href="{{ route('books.search') }}" class="btn-secondary">Browse all</a>
    </div>
    @if ($featuredBooks->isEmpty())
        <div class="mt-8"><x-empty-state title="Catalog is being prepared" message="Books will appear here as soon as the library team adds them." /></div>
    @else
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($featuredBooks as $book)
                <article class="panel overflow-hidden p-5 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex items-start gap-4">
                        <div class="grid h-28 w-20 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-700 px-2 text-center text-xs font-black text-white shadow-md">{{ str($book->title)->limit(24) }}</div>
                        <div class="min-w-0">
                            <x-badge :status="$book->available_copies > 0 ? 'available' : 'unavailable'" />
                            <h3 class="mt-3 line-clamp-2 font-bold">{{ $book->title }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $book->author }}</p>
                            <p class="mt-3 text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $book->category->name }}</p>
                            @if ($book->digitalAsset?->isReady())
                                <a href="{{ route('member.reader.open', $book) }}" class="mt-3 inline-flex text-sm font-bold text-emerald-600 dark:text-emerald-400">Baca online</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="overflow-hidden rounded-3xl bg-slate-950 px-6 py-10 text-white sm:px-10 lg:flex lg:items-center lg:justify-between">
        <div><p class="text-sm font-bold uppercase tracking-wider text-emerald-400">Ready to borrow?</p><h2 class="mt-2 text-3xl font-black">Join your campus library community.</h2><p class="mt-3 text-slate-300">Registration takes only a few minutes and is reviewed by a librarian.</p></div>
        <a href="{{ route('member.register') }}" class="mt-6 inline-flex rounded-xl bg-emerald-500 px-5 py-3 font-bold text-slate-950 hover:bg-emerald-400 lg:mt-0">Register as member</a>
    </div>
</section>
@endsection
