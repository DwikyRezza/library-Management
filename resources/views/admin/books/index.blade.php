@extends('layouts.admin')
@section('title', 'Books')
@section('page-title', 'Books')
@section('page-description', 'Search, filter, and manage the physical catalog')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div>
        @if(\App\Models\Book::exists())
            <button @click="$dispatch('open-modal', 'delete-all-books')" class="rounded-xl border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50 dark:border-red-900 dark:bg-slate-900 dark:text-red-400 dark:hover:bg-red-950">
                Delete All
            </button>
            <x-danger-modal name="delete-all-books" title="Hapus semua buku?" :action="route('admin.books.delete-all')">
                Apakah Anda yakin ingin menghapus semua buku? Buku dengan peminjaman aktif tidak akan dihapus.
            </x-danger-modal>
        @endif
    </div>
    <a href="{{ route('admin.books.create') }}" class="btn-primary">Add book</a>
</div>
<form method="GET" class="panel mb-6 grid gap-4 p-4 lg:grid-cols-[1fr_220px_180px_auto] items-center">
    <div class="relative flex-1 w-full">
        <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
    <button class="btn-primary w-full lg:w-auto px-6 py-2.5">Filter</button>
</form>
@if ($books->isEmpty())
    <x-empty-state title="No books found" message="Add a book or change the current filters.">
        <x-slot:action><a href="{{ route('admin.books.create') }}" class="btn-primary">Add book</a></x-slot:action>
    </x-empty-state>
@else
    <div class="flex flex-col gap-4">
        @foreach ($books as $book)
            <div class="panel p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 transition hover:border-slate-350 dark:hover:border-slate-700">
                <!-- Left side: Cover & Info -->
                <div class="flex items-center gap-4 min-w-0 flex-1">
                    <!-- Book Cover Placeholder or Image -->
                    @if($book->cover_image)
                        <img src="{{ $book->cover_image }}" class="w-16 h-22 object-cover rounded-lg shadow-sm shrink-0" alt="{{ $book->title }}">
                    @else
                        <div class="w-16 h-22 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-600 flex flex-col justify-between p-2 text-white shadow-sm shrink-0 select-none">
                            <span class="text-[8px] font-black uppercase tracking-wider opacity-75">Book</span>
                            <div class="font-extrabold text-[10px] leading-tight line-clamp-3">{{ $book->title }}</div>
                            <span class="text-[8px] opacity-75 truncate">{{ $book->author }}</span>
                        </div>
                    @endif

                    <!-- Details -->
                    <div class="min-w-0">
                        <a href="{{ route('admin.books.show', $book) }}" class="font-bold text-slate-900 dark:text-on-surface hover:text-blue-600 dark:hover:text-blue-400 text-base line-clamp-1">
                            {{ $book->title }}
                        </a>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 truncate">
                            by {{ $book->author }} · {{ $book->isbn ?: 'No ISBN' }}
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-slate-100 dark:bg-surface-container px-2 py-0.5 text-xs font-semibold text-slate-600 dark:text-on-surface-variant border border-slate-200 dark:border-slate-700/50">
                                {{ $book->category->name }}
                            </span>
                            <span class="text-xs text-slate-400 dark:text-slate-500">
                                {{ $book->publication_year ?: 'Year n/a' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right side: Copies, Status, and Action Buttons -->
                <div class="flex items-center justify-between md:justify-end gap-6 shrink-0 border-t border-slate-100 dark:border-slate-800 pt-3 md:pt-0 md:border-0">
                    <div class="text-left md:text-right">
                        <p class="text-xs text-slate-400 dark:text-slate-500">Available copies</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-on-surface-variant mt-0.5">
                            {{ $book->available_copies }} / {{ $book->total_copies }}
                        </p>
                    </div>

                    <div>
                        <x-badge :status="$book->available_copies > 0 ? 'available' : 'unavailable'" />
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.books.edit', $book) }}" class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-xs font-bold shadow transition-all active:scale-95">
                            Edit
                        </a>
                        <button @click="$dispatch('open-modal', 'delete-book-{{ $book->id }}')" class="rounded-lg bg-[#92002a] hover:bg-[#92002a]/95 text-white px-4 py-2 text-xs font-bold shadow transition-all active:scale-95">
                            Delete
                        </button>
                        <x-danger-modal name="delete-book-{{ $book->id }}" title="Hapus buku?" :action="route('admin.books.destroy', $book)" :book-title="$book->title" :book-author="$book->author">
                            Apakah Anda yakin ingin menghapus buku ini? Data akan dihapus secara lunak. <span class="text-white font-semibold underline decoration-red-500/40">Peminjaman aktif mencegah tindakan ini.</span>
                        </x-danger-modal>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $books->links() }}</div>
@endif
@endsection
