@extends('layouts.admin')
@section('title', $member->full_name)
@section('page-title', 'Member profile')
@section('page-description', 'Membership details, limits, and transaction history')
@section('content')
<div class="grid gap-6 xl:grid-cols-[360px_1fr]">
    <aside class="space-y-6">
        <section class="panel p-6">
            <div class="flex items-start justify-between"><div class="grid size-14 place-items-center rounded-2xl bg-indigo-100 text-xl font-black text-indigo-600 dark:bg-indigo-950">{{ strtoupper(substr($member->first_name, 0, 1).substr($member->last_name, 0, 1)) }}</div><x-badge :status="$member->status" /></div>
            <h2 class="mt-5 text-xl font-black">{{ $member->full_name }}</h2><p class="text-sm text-slate-500">{{ $member->member_code }}</p>
            <dl class="mt-6 space-y-4 text-sm"><div><dt class="text-slate-500">Email</dt><dd class="font-semibold">{{ $member->email }}</dd></div><div><dt class="text-slate-500">Roll number</dt><dd class="font-semibold">{{ $member->roll_number }}</dd></div><div><dt class="text-slate-500">Branch / year</dt><dd class="font-semibold">{{ $member->branch?->name ?: 'N/a' }} · {{ $member->year ?: 'N/a' }}</dd></div><div><dt class="text-slate-500">Loan quota</dt><dd class="font-semibold">{{ $member->books_borrowed_count }} of {{ $member->memberCategory->max_books }} books</dd></div></dl>
            <div class="mt-6 flex gap-3"><a href="{{ route('admin.members.edit', $member) }}" class="btn-secondary flex-1">Edit</a><button @click="$dispatch('open-modal', 'archive-member')" class="btn-danger">Archive</button></div>
        </section>
        @if ($member->status === \App\Models\Member::STATUS_PENDING)
            <section class="panel p-5"><h3 class="font-bold">Decision required</h3><div class="mt-4 flex gap-3"><button @click="$dispatch('open-modal', 'approve-member')" class="btn-primary">Approve</button><button @click="$dispatch('open-modal', 'reject-member')" class="btn-danger">Reject</button></div></section>
        @endif
    </aside>
    <section>
        <div class="panel overflow-hidden">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Transaction history</h2></div>
            @if ($transactions->isEmpty())<p class="p-6 text-sm text-slate-500">This member has no transactions.</p>@else<div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900"><tr><th class="px-5 py-3">Transaction</th><th class="px-5 py-3">Book</th><th class="px-5 py-3">Due</th><th class="px-5 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">@foreach ($transactions as $transaction)<tr><td class="px-5 py-4"><a class="font-mono text-xs font-bold text-indigo-600" href="{{ route('admin.transactions.show', $transaction) }}">{{ $transaction->transaction_code }}</a></td><td class="px-5 py-4 font-semibold">{{ $transaction->bookCopy->book->title }}<p class="text-xs text-slate-500">{{ $transaction->bookCopy->copy_code }}</p></td><td class="px-5 py-4">{{ $transaction->due_at->format('d M Y') }}</td><td class="px-5 py-4"><x-badge :status="$transaction->display_status" /></td></tr>@endforeach</tbody></table></div>@endif
        </div>
        <div class="mt-6">{{ $transactions->links() }}</div>
    </section>
</div>
<x-modal name="archive-member" title="Archive member?"><p>The member can only be archived when no active loans remain.</p><x-slot:actions><form method="POST" action="{{ route('admin.members.destroy', $member) }}">@csrf @method('DELETE')<button class="btn-danger">Archive</button></form></x-slot:actions></x-modal>
@if ($member->status === \App\Models\Member::STATUS_PENDING)
    <x-modal name="approve-member" title="Approve member?"><p>{{ $member->full_name }} will receive borrowing access based on the {{ $member->memberCategory->name }} quota.</p><x-slot:actions><form method="POST" action="{{ route('admin.members.approve', $member) }}">@csrf<button class="btn-primary">Approve</button></form></x-slot:actions></x-modal>
    <x-modal name="reject-member" title="Reject member?"><p>This finalizes the registration as rejected. Reactivation requires a separate future workflow.</p><x-slot:actions><form method="POST" action="{{ route('admin.members.reject', $member) }}">@csrf<button class="btn-danger">Reject</button></form></x-slot:actions></x-modal>
@endif
@endsection
