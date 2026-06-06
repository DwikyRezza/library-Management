@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Live library operations and quick actions')

@section('content')
@php
    $cards = [
        ['Total books', $stats['books'], 'bg-indigo-500'],
        ['Total copies', $stats['copies'], 'bg-blue-500'],
        ['Available', $stats['available_copies'], 'bg-emerald-500'],
        ['Borrowed', $stats['borrowed_copies'], 'bg-amber-500'],
        ['Members', $stats['members'], 'bg-violet-500'],
        ['Pending members', $stats['pending_members'], 'bg-orange-500'],
        ['Active loans', $stats['active_transactions'], 'bg-cyan-500'],
        ['Overdue', $stats['overdue_transactions'], 'bg-red-500'],
    ];
@endphp
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($cards as [$label, $value, $colorClass])
        <div class="panel p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $label }}</p>
                <span class="size-2.5 rounded-full {{ $colorClass }}"></span>
            </div>
            <p class="mt-3 text-3xl font-black">{{ number_format($value) }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('admin.books.create') }}" class="panel p-5 transition hover:border-indigo-400 hover:shadow-md"><p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">Quick action</p><h2 class="mt-2 font-bold">Add a new book</h2></a>
    <a href="{{ route('admin.circulation.index') }}#issue" class="panel p-5 transition hover:border-emerald-400 hover:shadow-md"><p class="text-sm font-bold text-emerald-600">Circulation</p><h2 class="mt-2 font-bold">Issue a book</h2></a>
    <a href="{{ route('admin.circulation.index') }}#return" class="panel p-5 transition hover:border-blue-400 hover:shadow-md"><p class="text-sm font-bold text-blue-600">Circulation</p><h2 class="mt-2 font-bold">Return a book</h2></a>
    <a href="{{ route('admin.members.pending') }}" class="panel p-5 transition hover:border-amber-400 hover:shadow-md"><p class="text-sm font-bold text-amber-600">Membership</p><h2 class="mt-2 font-bold">Review approvals</h2></a>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="panel overflow-hidden">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Recent borrowed books</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($recentBorrowed as $transaction)
                <a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 p-5 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                    <div><p class="font-semibold">{{ $transaction->bookCopy->book->title }}</p><p class="text-xs text-slate-500">{{ $transaction->member->full_name }} · {{ $transaction->bookCopy->copy_code }}</p></div>
                    <x-badge :status="$transaction->display_status" />
                </a>
            @empty
                <p class="p-5 text-sm text-slate-500">No active borrowing activity.</p>
            @endforelse
        </div>
    </section>
    <section class="panel overflow-hidden">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Recent returns</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($recentReturned as $transaction)
                <a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 p-5 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                    <div><p class="font-semibold">{{ $transaction->bookCopy->book->title }}</p><p class="text-xs text-slate-500">{{ $transaction->member->full_name }} · {{ optional($transaction->returned_at)->diffForHumans() }}</p></div>
                    <x-badge status="returned" />
                </a>
            @empty
                <p class="p-5 text-sm text-slate-500">No returned books yet.</p>
            @endforelse
        </div>
    </section>
</div>

<section class="panel mt-6 overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Pending member approvals</h2><a href="{{ route('admin.members.pending') }}" class="text-sm font-bold text-indigo-600">View all</a></div>
    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($pendingMembers as $member)
            <a href="{{ route('admin.members.show', $member) }}" class="rounded-xl border border-slate-200 p-4 hover:border-indigo-300 dark:border-slate-700"><p class="font-semibold">{{ $member->full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $member->roll_number }} · {{ $member->memberCategory->name }}</p></a>
        @empty
            <p class="text-sm text-slate-500">No registrations are waiting.</p>
        @endforelse
    </div>
</section>
@endsection
