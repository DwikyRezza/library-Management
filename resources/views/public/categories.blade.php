@extends('layouts.app')

@section('title', 'Categories - Lyrary')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="section-kicker">Browse by topic</p>
        <h1 class="mt-2 text-4xl font-black text-slate-950 dark:text-white">Categories</h1>
        <p class="mt-3 text-slate-500 dark:text-slate-400">Explore our collection organized by knowledge areas.</p>
    </div>

    @if ($categories->isEmpty())
        <div class="mt-8"><x-empty-state title="No categories yet" message="Categories will appear once the library team adds them." /></div>
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($categories as $category)
                <a href="{{ route('books.search', ['category' => $category->id]) }}"
                   class="panel interactive group flex items-center justify-between p-5 transition hover:border-[#00D1C1]/30">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-slate-950 group-hover:text-[#00D1C1] dark:text-white dark:group-hover:text-[#00D1C1]">{{ $category->name }}</h2>
                        @if($category->description)
                            <p class="mt-1 line-clamp-2 text-sm text-slate-500 dark:text-slate-400">{{ $category->description }}</p>
                        @endif
                    </div>
                    <div class="ml-4 flex shrink-0 flex-col items-end gap-1">
                        <span class="rounded-full bg-[#00D1C1]/10 px-3 py-1 text-sm font-bold text-[#00D1C1]">{{ $category->books_count }}</span>
                        <span class="text-xs text-slate-400">{{ $category->books_count === 1 ? 'book' : 'books' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
@endsection
