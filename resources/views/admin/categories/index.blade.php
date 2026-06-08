@extends('layouts.admin')

@section('title', 'Categories')
@section('page-title', 'Book categories')
@section('page-description', 'Organize the catalog into clear knowledge areas')

@section('content')
<div class="mb-5 flex justify-end">
    <a href="{{ route('admin.categories.create') }}" class="btn-primary">Add category</a>
</div>

@if ($categories->isEmpty())
    <x-empty-state title="No categories yet" message="Create the first category before adding books.">
        <x-slot:action><a href="{{ route('admin.categories.create') }}" class="btn-primary">Add category</a></x-slot:action>
    </x-empty-state>
@else
    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="soft-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Books</th>
                        <th>Description</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        @php
                            $colorClass = match ($category->color) {
                                'blue' => 'bg-blue-500',
                                'emerald' => 'bg-emerald-500',
                                'amber' => 'bg-amber-500',
                                'indigo' => 'bg-sky-500',
                                'rose' => 'bg-rose-500',
                                default => 'bg-slate-500',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="size-3 rounded-full {{ $colorClass }}"></span>
                                    <span class="font-semibold">{{ $category->name }}</span>
                                </div>
                            </td>
                            <td>{{ $category->books_count }}</td>
                            <td class="max-w-md text-slate-500 dark:text-slate-400">{{ $category->description ?: 'No description' }}</td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="subtle-link">Edit</a>
                                    <button type="button" @click="$dispatch('open-modal', 'delete-category-{{ $category->id }}')" class="font-semibold text-rose-700 hover:text-rose-900 dark:text-rose-200 dark:hover:text-rose-100">Delete</button>
                                </div>
                                <x-danger-modal name="delete-category-{{ $category->id }}" title="Delete category?" :action="route('admin.categories.destroy', $category)">
                                    This is only allowed when no books use {{ $category->name }}.
                                </x-danger-modal>
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
