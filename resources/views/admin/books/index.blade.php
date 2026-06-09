@extends('layouts.admin')

@section('title', 'Books')
@section('page-title', 'Books')
@section('page-description', 'Search, filter, and manage the physical catalog')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    @if(\App\Models\Book::exists())
        <button type="button" @click="$dispatch('open-modal', 'delete-all-books')" class="btn-secondary text-rose-700 hover:bg-rose-50 dark:text-rose-200 dark:hover:bg-rose-400/10">
            Delete all
        </button>
        <x-danger-modal name="delete-all-books" title="Hapus semua buku?" :action="route('admin.books.delete-all')" confirm-label="Hapus semua">
            Apakah Anda yakin ingin menghapus semua buku? Buku dengan peminjaman aktif tidak akan dihapus.
        </x-danger-modal>
    @else
        <span></span>
    @endif
    <a href="{{ route('admin.books.create') }}" class="btn-primary">Add book</a>
</div>

<form method="GET" class="panel mb-6 grid items-center gap-4 p-4 lg:grid-cols-[1fr_220px_180px_auto]">
    <div class="relative w-full">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </span>
        <input class="input pl-10" name="q" value="{{ request('q') }}" placeholder="Title, author, ISBN, category">
    </div>
    <select class="input" name="category">
        <option value="">All categories</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <select class="input" name="availability">
        <option value="">Any availability</option>
        <option value="available" @selected(request('availability') === 'available')>Available</option>
        <option value="unavailable" @selected(request('availability') === 'unavailable')>Unavailable</option>
    </select>
    <button class="btn-primary">Filter</button>
</form>

@if ($books->isEmpty())
    <x-empty-state title="No books found" message="Add a book or change the current filters.">
        <x-slot:action><a href="{{ route('admin.books.create') }}" class="btn-primary">Add book</a></x-slot:action>
    </x-empty-state>
@else
    <div class="space-y-4">
        @foreach ($books as $book)
            <article class="panel p-4 transition hover:border-blue-200 hover:bg-white dark:hover:border-blue-400/20 dark:hover:bg-slate-900/90">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex min-w-0 flex-1 items-center gap-4">
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
                            <a href="{{ route('admin.books.show', $book) }}" class="line-clamp-1 text-base font-bold text-slate-950 hover:text-blue-700 dark:text-white dark:hover:text-blue-200">
                                {{ $book->title }}
                            </a>
                            <p class="mt-1 truncate text-sm text-slate-500 dark:text-slate-400">
                                by {{ $book->author }} - {{ $book->isbn ?: 'No ISBN' }}
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                    {{ $book->category->name }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $book->publication_year ?: 'Year n/a' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 border-t border-slate-100 pt-4 dark:border-white/10 md:min-w-[310px] md:border-t-0 md:pt-0">
                        <div class="flex items-center justify-between gap-5 md:justify-end">
                            <div class="text-left md:text-right">
                                <p class="text-xs text-slate-400">Available copies</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ $book->available_copies }} / {{ $book->total_copies }}
                                </p>
                            </div>
                            <x-badge :status="$book->available_copies > 0 ? 'available' : 'unavailable'" />
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <a href="{{ route('admin.books.edit', $book) }}" class="btn-secondary px-3 py-2 text-xs">Edit</a>
                            <button type="button" @click="$dispatch('open-modal', 'delete-book-{{ $book->id }}')" class="btn-danger px-3 py-2 text-xs">Hapus</button>
                            <x-danger-modal name="delete-book-{{ $book->id }}" title="Hapus buku?" :action="route('admin.books.destroy', $book)" :book-title="$book->title" :book-author="$book->author" confirm-label="Hapus buku">
                                Apakah Anda yakin ingin menghapus buku ini? Data akan dihapus secara lunak. Peminjaman aktif akan mencegah tindakan ini.
                            </x-danger-modal>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-6">{{ $books->links() }}</div>
@endif
@endsection
