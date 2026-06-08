@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Live library operations and quick actions')

@section('content')
@php
    $cards = [
        ['label' => 'Total books', 'value' => $stats['books'], 'tone' => 'bg-blue-500'],
        ['label' => 'Total copies', 'value' => $stats['copies'], 'tone' => 'bg-sky-500'],
        ['label' => 'Available', 'value' => $stats['available_copies'], 'tone' => 'bg-emerald-500'],
        ['label' => 'Borrowed', 'value' => $stats['borrowed_copies'], 'tone' => 'bg-amber-500'],
        ['label' => 'Members', 'value' => $stats['members'], 'tone' => 'bg-cyan-500'],
        ['label' => 'Pending members', 'value' => $stats['pending_members'], 'tone' => 'bg-orange-500'],
        ['label' => 'Active loans', 'value' => $stats['active_transactions'], 'tone' => 'bg-teal-500'],
        ['label' => 'Overdue', 'value' => $stats['overdue_transactions'], 'tone' => 'bg-rose-500'],
    ];
@endphp

<div class="space-y-6">
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($cards as $card)
            <article class="panel p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ number_format($card['value']) }}</p>
                    </div>
                    <span class="h-10 w-2 rounded-full {{ $card['tone'] }}"></span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <a href="{{ route('admin.books.create') }}" class="panel p-5 transition hover:border-blue-300 hover:bg-blue-50/40 dark:hover:border-blue-400/30 dark:hover:bg-blue-500/10">
            <p class="section-kicker">Catalog</p>
            <h2 class="mt-2 text-lg font-bold">Add a new book</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create metadata, copies, and optional digital assets.</p>
        </a>
        <a href="{{ route('admin.members.pending') }}" class="panel p-5 transition hover:border-amber-300 hover:bg-amber-50/40 dark:hover:border-amber-400/30 dark:hover:bg-amber-500/10">
            <p class="section-kicker text-amber-600 dark:text-amber-300">Approvals</p>
            <h2 class="mt-2 text-lg font-bold">Review pending members</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ number_format($stats['pending_members']) }} registration waiting.</p>
        </a>
        <a href="{{ route('admin.circulation.index') }}" class="panel p-5 transition hover:border-emerald-300 hover:bg-emerald-50/40 dark:hover:border-emerald-400/30 dark:hover:bg-emerald-500/10">
            <p class="section-kicker text-emerald-600 dark:text-emerald-300">Circulation</p>
            <h2 class="mt-2 text-lg font-bold">Manage active loans</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Borrowing, returns, and overdue monitoring.</p>
        </a>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-white/10">
                <div>
                    <p class="section-kicker">Borrowing</p>
                    <h2 class="mt-1 font-bold">Recent borrowed books</h2>
                </div>
                <a href="{{ route('admin.transactions.index', ['status' => 'borrowed']) }}" class="subtle-link text-sm">View all</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-white/10">
                @forelse ($recentBorrowed as $transaction)
                    <a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 p-5 transition hover:bg-slate-50 dark:hover:bg-white/5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $transaction->bookCopy?->book?->title ?? 'Buku telah dihapus' }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $transaction->member?->name ?? 'Member umum' }}</p>
                        </div>
                        <x-badge :status="$transaction->display_status" />
                    </a>
                @empty
                    <p class="p-5 text-sm text-slate-500 dark:text-slate-400">No active borrowing activity.</p>
                @endforelse
            </div>
        </section>

        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-white/10">
                <div>
                    <p class="section-kicker">Returns</p>
                    <h2 class="mt-1 font-bold">Recent returns</h2>
                </div>
                <a href="{{ route('admin.transactions.index', ['status' => 'returned']) }}" class="subtle-link text-sm">View all</a>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-white/10">
                @forelse ($recentReturned as $transaction)
                    <a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 p-5 transition hover:bg-slate-50 dark:hover:bg-white/5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $transaction->bookCopy?->book?->title ?? 'Buku telah dihapus' }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $transaction->member?->name ?? 'Member umum' }}</p>
                        </div>
                        <x-badge status="returned" />
                    </a>
                @empty
                    <p class="p-5 text-sm text-slate-500 dark:text-slate-400">No returned books yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="panel overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-white/10">
            <div>
                <p class="section-kicker">Members</p>
                <h2 class="mt-1 font-bold">Pending member approvals</h2>
            </div>
            <a href="{{ route('admin.members.pending') }}" class="subtle-link text-sm">Open queue</a>
        </div>
        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($pendingMembers as $member)
                <a href="{{ route('admin.members.show', $member) }}" class="rounded-lg border border-slate-200 bg-white/60 p-4 transition hover:border-blue-300 hover:bg-blue-50/40 dark:border-white/10 dark:bg-white/5 dark:hover:border-blue-400/30 dark:hover:bg-blue-500/10">
                    <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $member->name }}</p>
                    <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $member->email }}</p>
                </a>
            @empty
                <p class="p-2 text-sm text-slate-500 dark:text-slate-400">No registrations are waiting.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
