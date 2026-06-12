@extends('layouts.app')

@section('title', 'Booklist - Lyrary')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker">Member library</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">Booklist</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Buku yang ingin Anda pinjam atau baca nanti.</p>
        </div>
        <a href="{{ route('books.search') }}" class="btn-secondary self-start">Browse catalog</a>
    </div>

    @if ($books->isEmpty())
        <div class="mt-8">
            <x-empty-state title="Booklist masih kosong" message="Simpan buku dari katalog agar mudah ditemukan kembali." />
        </div>
    @else
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($books as $book)
                <article class="panel interactive p-5">
                    <div class="flex items-start gap-4">
                        <div class="book-cover">
                            <span class="text-[9px] font-black uppercase text-blue-500 dark:text-blue-200">Book</span>
                            <span class="line-clamp-4 text-xs font-black leading-tight">{{ $book->title }}</span>
                            <span class="truncate text-[10px] text-blue-700/70 dark:text-blue-100/70">{{ $book->author }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <x-badge :status="$book->available_copies > 0 ? 'available' : 'unavailable'" />
                            <h2 class="mt-3 line-clamp-2 text-lg font-bold text-slate-950 dark:text-white">{{ $book->title }}</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $book->author }}</p>
                            <p class="mt-3 text-xs font-bold text-blue-700 dark:text-blue-300">{{ $book->category->name }}</p>
                            <x-book-reader-action :book="$book" compact />
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $books->links() }}</div>
    @endif
</section>
@endsection
