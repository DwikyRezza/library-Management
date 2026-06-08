@extends('layouts.app')

@section('title', 'Book catalog - LibraFlow')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="max-w-2xl"><p class="font-bold text-indigo-600 dark:text-indigo-400">Public catalog</p><h1 class="mt-2 text-4xl font-black">Search books and availability</h1><p class="mt-3 text-slate-500 dark:text-slate-400">Explore by title, author, ISBN, or category.</p></div>
    <form method="GET" class="panel mt-8 grid gap-3 p-4 md:grid-cols-[1fr_240px_auto]">
        <input name="q" value="{{ request('q') }}" class="input" placeholder="What are you looking for?">
        <select name="category" class="input">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <button class="btn-primary">Search</button>
    </form>

    @if ($books->isEmpty())
        <div class="mt-8"><x-empty-state title="No matching books" message="Try a broader keyword or choose a different category." /></div>
    @else
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($books as $book)
                <article class="panel p-5">
                    <div class="flex items-start justify-between gap-4">
                        <x-badge :status="$book->available_copies > 0 ? 'available' : 'unavailable'" />
                        <span class="text-xs font-semibold text-slate-400">{{ $book->available_copies }}/{{ $book->total_copies }} copies</span>
                    </div>
                    <h2 class="mt-4 text-lg font-bold">{{ $book->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $book->author }}</p>
                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 dark:border-slate-800">
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $book->category->name }}</span>
                        <span class="text-xs text-slate-400">{{ $book->publication_year ?: 'Year n/a' }}</span>
                    </div>
                    @auth('member')
                        @if ($book->digitalAsset?->isReady())
                            <a href="{{ route('member.reader.open', $book) }}" class="btn-primary mt-4 w-full">Read</a>
                        @endif
                    @endauth
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $books->links() }}</div>
    @endif
</section>
@endsection
