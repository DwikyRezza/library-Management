@extends('layouts.admin')

@section('title', 'Transactions')
@section('page-title', 'Transaction history')
@section('page-description', 'Search and audit every issue and return')

@section('content')
<form method="GET" class="panel mb-6 grid gap-3 p-4 xl:grid-cols-[1fr_180px_170px_170px_auto]">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Transaction, book, copy, or member">
    <select class="input" name="status">
        <option value="">All statuses</option>
        @foreach (['borrowed', 'returned', 'overdue'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <input class="input" type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Date from">
    <input class="input" type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Date to">
    <button class="btn-primary">Filter</button>
</form>

@if ($transactions->isEmpty())
    <x-empty-state title="No transactions found" message="No circulation history matches these filters." />
@else
    <div class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="soft-table">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Book</th>
                        <th>Member</th>
                        <th>Dates</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr>
                            <td>
                                <a href="{{ route('admin.transactions.show', $transaction) }}" class="font-mono text-xs font-bold text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-100">{{ $transaction->transaction_code }}</a>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">by {{ $transaction->issuedBy->name }}</p>
                            </td>
                            <td class="font-semibold">
                                {{ $transaction->bookCopy->book->title }}
                                <p class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $transaction->bookCopy->copy_code }}</p>
                            </td>
                            <td>
                                {{ $transaction->member->full_name }}
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $transaction->member->member_code }}</p>
                            </td>
                            <td class="text-xs">
                                <p>Issued {{ $transaction->issued_at->format('d M Y') }}</p>
                                <p class="mt-1 text-slate-500 dark:text-slate-400">Due {{ $transaction->due_at->format('d M Y') }}</p>
                                @if ($transaction->returned_at)
                                    <p class="mt-1 font-semibold text-emerald-700 dark:text-emerald-200">Returned {{ $transaction->returned_at->format('d M Y') }}</p>
                                @endif
                            </td>
                            <td>
                                <x-badge :status="$transaction->display_status" />
                                @if ($transaction->is_overdue)
                                    <p class="mt-1 text-xs font-bold text-rose-700 dark:text-rose-200">{{ $transaction->days_overdue }} days late</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $transactions->links() }}</div>
@endif
@endsection
