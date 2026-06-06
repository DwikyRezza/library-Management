@extends('layouts.admin')
@section('title', $transaction->transaction_code)
@section('page-title', 'Transaction detail')
@section('page-description', 'Complete issue and return audit trail')
@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <section class="panel p-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"><div><p class="font-mono text-sm font-bold text-indigo-600">{{ $transaction->transaction_code }}</p><h2 class="mt-2 text-2xl font-black">{{ $transaction->bookCopy->book->title }}</h2><p class="mt-1 text-slate-500">{{ $transaction->bookCopy->copy_code }}</p></div><x-badge :status="$transaction->display_status" /></div>
        @if ($transaction->is_overdue)<div class="mt-5 rounded-xl bg-red-50 p-4 text-sm font-semibold text-red-700 dark:bg-red-950 dark:text-red-300">This loan is {{ $transaction->days_overdue }} days overdue.</div>@endif
    </section>
    <div class="grid gap-6 md:grid-cols-2">
        <section class="panel p-6"><h3 class="font-bold">Member</h3><a href="{{ route('admin.members.show', $transaction->member) }}" class="mt-4 block text-lg font-bold text-indigo-600">{{ $transaction->member->full_name }}</a><p class="mt-1 text-sm text-slate-500">{{ $transaction->member->member_code }} · {{ $transaction->member->roll_number }}</p><p class="mt-4 text-sm">{{ $transaction->member->memberCategory->name }} · {{ $transaction->member->branch?->name ?: 'No branch' }}</p></section>
        <section class="panel p-6"><h3 class="font-bold">Timeline</h3><dl class="mt-4 space-y-4 text-sm"><div><dt class="text-slate-500">Issued</dt><dd class="font-semibold">{{ $transaction->issued_at->format('d M Y, H:i') }} by {{ $transaction->issuedBy->name }}</dd></div><div><dt class="text-slate-500">Due</dt><dd class="font-semibold">{{ $transaction->due_at->format('d M Y, H:i') }}</dd></div><div><dt class="text-slate-500">Returned</dt><dd class="font-semibold">{{ $transaction->returned_at?->format('d M Y, H:i') ?: 'Not returned' }}@if ($transaction->returnedBy) by {{ $transaction->returnedBy->name }}@endif</dd></div></dl></section>
    </div>
    <section class="panel p-6"><h3 class="font-bold">Notes</h3><p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $transaction->notes ?: 'No notes were recorded.' }}</p></section>
    <a href="{{ route('admin.transactions.index') }}" class="btn-secondary">Back to history</a>
</div>
@endsection
