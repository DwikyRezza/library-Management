@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('page-description', 'Operational insights and portable CSV exports')

@section('content')
<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
    <div class="panel p-5">
        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Transactions this month</p>
        <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ number_format($monthlyTransactionCount) }}</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.reports.books.export') }}" class="btn-secondary">Export books CSV</a>
        <a href="{{ route('admin.reports.transactions.export') }}" class="btn-primary">Export transactions CSV</a>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="panel overflow-hidden">
        <div class="border-b border-slate-200 p-5 dark:border-white/10"><h2 class="font-bold">Most borrowed books</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-white/10">
            @forelse ($topBooks as $book)
                <div class="flex items-center justify-between gap-4 p-5">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $book->title }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $book->author }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-black text-blue-700 dark:text-blue-300">{{ $book->borrow_count }} loans</span>
                </div>
            @empty
                <p class="p-5 text-sm text-slate-500 dark:text-slate-400">No borrowing data.</p>
            @endforelse
        </div>
    </section>

    <section class="panel overflow-hidden">
        <div class="border-b border-slate-200 p-5 dark:border-white/10"><h2 class="font-bold">Most active members</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-white/10">
            @forelse ($activeMembers as $member)
                <a href="{{ route('admin.members.show', $member) }}" class="flex items-center justify-between gap-4 p-5 transition hover:bg-slate-50 dark:hover:bg-white/5">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $member->full_name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $member->member_code }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-black text-emerald-700 dark:text-emerald-200">{{ $member->transaction_count }} loans</span>
                </a>
            @empty
                <p class="p-5 text-sm text-slate-500 dark:text-slate-400">No member activity.</p>
            @endforelse
        </div>
    </section>

    <section class="panel overflow-hidden">
        <div class="border-b border-slate-200 p-5 dark:border-white/10"><h2 class="font-bold">Currently borrowed</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-white/10">
            @forelse ($borrowedBooks as $transaction)
                <a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 p-5 transition hover:bg-slate-50 dark:hover:bg-white/5">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $transaction->bookCopy->book->title }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $transaction->member->full_name }} - due {{ $transaction->due_at->format('d M') }}</p>
                    </div>
                    <x-badge :status="$transaction->display_status" />
                </a>
            @empty
                <p class="p-5 text-sm text-slate-500 dark:text-slate-400">No active loans.</p>
            @endforelse
        </div>
    </section>

    <section class="panel overflow-hidden">
        <div class="border-b border-slate-200 p-5 dark:border-white/10"><h2 class="font-bold">Overdue books</h2></div>
        <div class="divide-y divide-slate-100 dark:divide-white/10">
            @forelse ($overdueBooks as $transaction)
                <a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 p-5 transition hover:bg-slate-50 dark:hover:bg-white/5">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $transaction->bookCopy->book->title }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $transaction->member->full_name }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-black text-rose-700 dark:text-rose-200">{{ $transaction->days_overdue }} days</span>
                </a>
            @empty
                <p class="p-5 text-sm text-slate-500 dark:text-slate-400">No overdue loans.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
