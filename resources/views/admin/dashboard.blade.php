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
    @foreach($cards as $card)
        <div class="panel p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card[0] }}</p>
                <p class="text-2xl font-bold mt-1 text-slate-900 dark:text-white">{{ $card[1] }}</p>
            </div>
            <div class="w-3 h-12 rounded-full {{ $card[2] }}"></div>
        </div>
    @endforeach
    <a href="{{ route('admin.members.pending') }}" class="panel p-5 transition hover:border-amber-400 hover:shadow-md">
        <p class="text-sm font-bold text-amber-500">View Pending Approvals →</p>
    </a>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="panel overflow-hidden">
        <div class="border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Recent borrowed books</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($recentBorrowed as $transaction)
                <a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 p-5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                    <div>
                        <p class="font-semibold">{{ $transaction->bookCopy?->book?->title ?? 'Buku Telah Dihapus' }}</p>
                        <p class="text-xs text-slate-500">{{ $transaction->member?->name ?? 'Member Umum' }}</p>
                    </div>
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
                <a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 p-5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                    <div>
                        <p class="font-semibold">{{ $transaction->bookCopy?->book?->title ?? 'Buku Telah Dihapus' }}</p>
                        <p class="text-xs text-slate-500">{{ $transaction->member?->name ?? 'Member Umum' }}</p>
                    </div>
                    <x-badge status="returned" />
                </a>
            @empty
                <p class="p-5 text-sm text-slate-500">No returned books yet.</p>
            @endforelse
        </div>
    </section>
</div>

<section class="panel mt-6 overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Pending member approvals</h2></div>
    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($pendingMembers as $member)
            <a href="{{ route('admin.members.show', $member) }}" class="rounded-xl border border-slate-200 p-4 hover:border-indigo-300 dark:border-slate-700 transition">
                <p class="font-medium text-slate-900 dark:text-white">{{ $member->name }}</p>
                <p class="text-xs text-slate-500">{{ $member->email }}</p>
            </a>
        @empty
            <p class="text-sm text-slate-500 p-2">No registrations are waiting.</p>
        @endforelse
    </div>
</section>
@endsection
