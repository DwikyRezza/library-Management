@extends('layouts.admin')
@section('title', 'Circulation')
@section('page-title', 'Circulation')
@section('page-description', 'Issue and return physical book copies')
@section('content')
<div class="grid gap-6 xl:grid-cols-2">
    <section id="issue" class="panel p-6" x-data="{ submitting: false }">
        <div><p class="text-sm font-bold text-emerald-600">Checkout</p><h2 class="mt-1 text-xl font-black">Issue a book</h2><p class="mt-2 text-sm text-slate-500">The member approval, quota, copy state, and active transaction are checked again at submit time.</p></div>
        <form id="issue-form" method="POST" action="{{ route('admin.circulation.issue') }}" class="mt-6 space-y-4" @submit="submitting = true">@csrf<div><label class="label">Book copy code</label><input class="input" name="book_copy_code" value="{{ old('book_copy_code') }}" placeholder="LIB-0001-001" required></div><div><label class="label">Member code or roll number</label><input class="input" name="member_lookup" value="{{ old('member_lookup') }}" placeholder="MBR-00001 or STU-2026-001" required></div><div><label class="label">Notes</label><textarea class="input" name="notes">{{ old('notes') }}</textarea></div><button type="button" @click="$dispatch('open-modal', 'issue-confirm')" class="btn-primary w-full">Review issue</button></form>
        <x-modal name="issue-confirm" title="Issue this book?"><p>LibraFlow will create an active loan and update copy, member, and book counters in one transaction.</p><x-slot:actions><button form="issue-form" class="btn-primary" :disabled="submitting"><span x-text="submitting ? 'Issuing...' : 'Issue book'"></span></button></x-slot:actions></x-modal>
    </section>
    <section id="return" class="panel p-6" x-data="{ submitting: false }">
        <div><p class="text-sm font-bold text-blue-600">Check-in</p><h2 class="mt-1 text-xl font-black">Return a book</h2><p class="mt-2 text-sm text-slate-500">Only a copy with an active borrowed or overdue transaction can be returned.</p></div>
        <form id="return-form" method="POST" action="{{ route('admin.circulation.return') }}" class="mt-6 space-y-4" @submit="submitting = true">@csrf<div><label class="label">Book copy code</label><input class="input" name="book_copy_code" placeholder="LIB-0001-001" required></div><div><label class="label">Return note</label><textarea class="input" name="notes" placeholder="Condition on return"></textarea></div><button type="button" @click="$dispatch('open-modal', 'return-confirm')" class="btn-primary w-full">Review return</button></form>
        <x-modal name="return-confirm" title="Return this book?"><p>The active loan will be closed and availability counters updated atomically.</p><x-slot:actions><button form="return-form" class="btn-primary" :disabled="submitting"><span x-text="submitting ? 'Returning...' : 'Return book'"></span></button></x-slot:actions></x-modal>
    </section>
</div>
<section class="panel mt-6 overflow-hidden">
    <div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Active loans</h2><a href="{{ route('admin.transactions.index') }}" class="text-sm font-bold text-indigo-600">Full history</a></div>
    @if ($activeTransactions->isEmpty())<p class="p-6 text-sm text-slate-500">No active borrowing transactions.</p>@else<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900"><tr><th class="px-5 py-3">Book / copy</th><th class="px-5 py-3">Member</th><th class="px-5 py-3">Due</th><th class="px-5 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">@foreach ($activeTransactions as $transaction)<tr><td class="px-5 py-4 font-semibold">{{ $transaction->bookCopy->book->title }}<p class="font-mono text-xs text-slate-500">{{ $transaction->bookCopy->copy_code }}</p></td><td class="px-5 py-4">{{ $transaction->member->full_name }}</td><td class="px-5 py-4">{{ $transaction->due_at->format('d M Y') }}@if ($transaction->is_overdue)<p class="text-xs font-bold text-red-600">{{ $transaction->days_overdue }} days late</p>@endif</td><td class="px-5 py-4"><x-badge :status="$transaction->display_status" /></td></tr>@endforeach</tbody></table></div>@endif
</section>
<div class="mt-6">{{ $activeTransactions->links() }}</div>
@endsection
