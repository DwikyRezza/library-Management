@extends('layouts.admin')
@section('title', 'Pending approvals')
@section('page-title', 'Pending approvals')
@section('page-description', 'Review new member registrations')
@section('content')
@if ($members->isEmpty())
    <x-empty-state title="Approval queue is clear" message="New member registrations will appear here." />
@else
    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($members as $member)
            <article class="panel p-5">
                <div class="flex items-start justify-between gap-4"><div><h2 class="font-bold">{{ $member->full_name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $member->roll_number }} · {{ $member->branch?->name ?: 'No branch' }}</p></div><x-badge status="pending" /></div>
                <dl class="mt-5 grid grid-cols-2 gap-4 text-sm"><div><dt class="text-slate-500">Email</dt><dd class="mt-1 font-semibold">{{ $member->email }}</dd></div><div><dt class="text-slate-500">Category</dt><dd class="mt-1 font-semibold">{{ $member->memberCategory->name }}</dd></div></dl>
                <div class="mt-5 flex gap-3"><a href="{{ route('admin.members.show', $member) }}" class="btn-secondary">View profile</a><button @click="$dispatch('open-modal', 'approve-{{ $member->id }}')" class="btn-primary">Approve</button><button @click="$dispatch('open-modal', 'reject-{{ $member->id }}')" class="btn-danger">Reject</button></div>
                <x-modal name="approve-{{ $member->id }}" title="Approve member?"><p>{{ $member->full_name }} will be allowed to borrow up to {{ $member->memberCategory->max_books }} books.</p><x-slot:actions><form method="POST" action="{{ route('admin.members.approve', $member) }}">@csrf<button class="btn-primary">Approve</button></form></x-slot:actions></x-modal>
                <x-modal name="reject-{{ $member->id }}" title="Reject member?"><p>This decision finalizes the registration. A separate reactivation flow would be required later.</p><x-slot:actions><form method="POST" action="{{ route('admin.members.reject', $member) }}">@csrf<button class="btn-danger">Reject</button></form></x-slot:actions></x-modal>
            </article>
        @endforeach
    </div>
    <div class="mt-6">{{ $members->links() }}</div>
@endif
@endsection
