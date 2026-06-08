@extends('layouts.admin')

@section('title', 'Members')
@section('page-title', 'Members')
@section('page-description', 'Search profiles, status, quotas, and history')

@section('content')
<form method="GET" class="panel mb-6 grid gap-3 p-4 lg:grid-cols-[1fr_180px_240px_auto]">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Name, email, roll or member code">
    <select class="input" name="status">
        <option value="">All statuses</option>
        @foreach (['pending', 'approved', 'rejected'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <select class="input" name="category">
        <option value="">All categories</option>
        @foreach ($memberCategories as $category)
            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <button class="btn-primary">Filter</button>
</form>

@if ($members->isEmpty())
    <x-empty-state title="No members found" message="No member profile matches the current filters." />
@else
    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="soft-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Category</th>
                        <th>Borrowed</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr>
                            <td>
                                <a href="{{ route('admin.members.show', $member) }}" class="font-bold text-slate-950 hover:text-blue-700 dark:text-white dark:hover:text-blue-200">{{ $member->full_name }}</a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $member->member_code }} - {{ $member->roll_number }}</p>
                            </td>
                            <td>
                                {{ $member->memberCategory->name }}
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $member->branch?->name ?: 'No branch' }}</p>
                            </td>
                            <td>{{ $member->books_borrowed_count }} / {{ $member->memberCategory->max_books }}</td>
                            <td><x-badge :status="$member->status" /></td>
                            <td class="text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a class="btn-secondary px-3 py-2 text-xs" href="{{ route('admin.members.show', $member) }}">View</a>
                                    @if (auth()->user()->isAdmin())
                                        <button type="button" @click="$dispatch('open-modal', 'delete-member-{{ $member->id }}')" class="btn-danger px-3 py-2 text-xs">Hapus member</button>
                                        <x-danger-modal name="delete-member-{{ $member->id }}" title="Hapus member?" :action="route('admin.members.destroy', $member)" confirm-label="Hapus member">
                                            Hapus member {{ $member->full_name }}? Member hanya dapat dihapus jika tidak memiliki peminjaman aktif.
                                        </x-danger-modal>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $members->links() }}</div>
@endif
@endsection
