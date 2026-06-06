@extends('layouts.admin')

@section('title', 'Categories')
@section('page-title', 'Book categories')
@section('page-description', 'Organize the catalog into clear knowledge areas')

@section('content')
<div class="mb-5 flex justify-end"><a href="{{ route('admin.categories.create') }}" class="btn-primary">Add category</a></div>
@if ($categories->isEmpty())
    <x-empty-state title="No categories yet" message="Create the first category before adding books.">
        <x-slot:action><a href="{{ route('admin.categories.create') }}" class="btn-primary">Add category</a></x-slot:action>
    </x-empty-state>
@else
    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-900/60"><tr><th class="px-5 py-3">Category</th><th class="px-5 py-3">Books</th><th class="px-5 py-3">Description</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($categories as $category)
                        @php
                            $colorClass = match ($category->color) {
                                'blue' => 'bg-blue-500',
                                'emerald' => 'bg-emerald-500',
                                'amber' => 'bg-amber-500',
                                'indigo' => 'bg-indigo-500',
                                'rose' => 'bg-rose-500',
                                default => 'bg-slate-500',
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-4"><div class="flex items-center gap-3"><span class="size-3 rounded-full {{ $colorClass }}"></span><span class="font-semibold">{{ $category->name }}</span></div></td>
                            <td class="px-5 py-4">{{ $category->books_count }}</td>
                            <td class="max-w-md px-5 py-4 text-slate-500">{{ $category->description ?: 'No description' }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="font-semibold text-indigo-600">Edit</a>
                                <button @click="$dispatch('open-modal', 'delete-category-{{ $category->id }}')" class="ml-3 font-semibold text-red-600">Delete</button>
                                <x-modal name="delete-category-{{ $category->id }}" title="Delete category?">
                                    <p>This is only allowed when no books use <strong>{{ $category->name }}</strong>.</p>
                                    <x-slot:actions><form method="POST" action="{{ route('admin.categories.destroy', $category) }}">@csrf @method('DELETE')<button class="btn-danger">Delete</button></form></x-slot:actions>
                                </x-modal>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $categories->links() }}</div>
@endif
@endsection
