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
            <x-modal name="delete-all-books" title="Delete all books?">
                <div class="p-1">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Are you sure you want to delete all books? Books with active loans will not be deleted.</p>
                </div>
                <x-slot:actions>
                    <form method="POST" action="{{ route('admin.books.delete-all') }}">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-500">Delete All</button>
                    </form>
                </x-slot:actions>
            </x-modal>
        @endif
    </div>
    <a href="{{ route('admin.books.create') }}" class="btn-primary">Add book</a>
</div>
<form method="GET" class="panel mb-6 grid gap-3 p-4 lg:grid-cols-[1fr_220px_180px_auto]">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Title, author, ISBN, category">
    <select class="input" name="category"><option value="">All categories</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select>
    <select class="input" name="availability"><option value="">Any availability</option><option value="available" @selected(request('availability') === 'available')>Available</option><option value="unavailable" @selected(request('availability') === 'unavailable')>Unavailable</option></select>
    <button class="btn-primary">Filter</button>
</form>
@if ($books->isEmpty())
    <x-empty-state title="No books found" message="Add a book or change the current filters."><x-slot:action><a href="{{ route('admin.books.create') }}" class="btn-primary">Add book</a></x-slot:action></x-empty-state>
@else
    <div class="panel overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-900/60"><tr><th class="px-5 py-3">Book</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Copies</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">@foreach ($books as $book)<tr>
            <td class="px-5 py-4"><a href="{{ route('admin.books.show', $book) }}" class="font-bold hover:text-indigo-600">{{ $book->title }}</a><p class="mt-1 text-xs text-slate-500">{{ $book->author }} · {{ $book->isbn ?: 'No ISBN' }}</p></td>
            <td class="px-5 py-4">{{ $book->category->name }}</td>
            <td class="px-5 py-4">{{ $book->available_copies }} / {{ $book->total_copies }}</td>
            <td class="px-5 py-4"><x-badge :status="$book->available_copies > 0 ? 'available' : 'unavailable'" /></td>
            <td class="px-5 py-4 text-right">
                <a class="font-semibold text-indigo-600" href="{{ route('admin.books.edit', $book) }}">Edit</a>
                <button @click="$dispatch('open-modal', 'delete-book-{{ $book->id }}')" class="ml-3 font-semibold text-red-600">Delete</button>
                <x-modal name="delete-book-{{ $book->id }}" title="Delete book?">
                    <div class="p-1 text-left">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Are you sure you want to delete this book? The record will be soft deleted. Active loans prevent this action.</p>
                    </div>
                    <x-slot:actions>
                        <form method="POST" action="{{ route('admin.books.destroy', $book) }}">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-500">Delete</button>
                        </form>
                    </x-slot:actions>
                </x-modal>
            </td>
        </tr>@endforeach</tbody>
    </table></div></div>
    <div class="mt-6">{{ $books->links() }}</div>
@endif
@endsection
