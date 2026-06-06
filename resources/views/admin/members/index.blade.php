@extends('layouts.admin')
@section('title', 'Members')
@section('page-title', 'Members')
@section('page-description', 'Search profiles, status, quotas, and history')
@section('content')
<form method="GET" class="panel mb-6 grid gap-3 p-4 lg:grid-cols-[1fr_180px_240px_auto]">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Name, email, roll or member code">
    <select class="input" name="status"><option value="">All statuses</option>@foreach (['pending', 'approved', 'rejected'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
    <select class="input" name="category"><option value="">All categories</option>@foreach ($memberCategories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select>
    <button class="btn-primary">Filter</button>
</form>
@if ($members->isEmpty())
    <x-empty-state title="No members found" message="No member profile matches the current filters." />
@else
    <div class="panel overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900"><tr><th class="px-5 py-3">Member</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Borrowed</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">@foreach ($members as $member)<tr>
            <td class="px-5 py-4"><a href="{{ route('admin.members.show', $member) }}" class="font-bold hover:text-indigo-600">{{ $member->full_name }}</a><p class="mt-1 text-xs text-slate-500">{{ $member->member_code }} · {{ $member->roll_number }}</p></td>
            <td class="px-5 py-4">{{ $member->memberCategory->name }}<p class="text-xs text-slate-500">{{ $member->branch?->name ?: 'No branch' }}</p></td>
            <td class="px-5 py-4">{{ $member->books_borrowed_count }} / {{ $member->memberCategory->max_books }}</td>
            <td class="px-5 py-4"><x-badge :status="$member->status" /></td>
            <td class="px-5 py-4 text-right"><a class="font-semibold text-indigo-600" href="{{ route('admin.members.show', $member) }}">View</a></td>
        </tr>@endforeach</tbody>
    </table></div></div>
    <div class="mt-6">{{ $members->links() }}</div>
@endif
@endsection
