@extends('layouts.admin')

@section('title', $member->full_name)
@section('page-title', 'Member profile')
@section('page-description', 'Membership details, limits, and transaction history')

@section('content')
<div class="grid gap-6 xl:grid-cols-[360px_1fr]">
    <aside class="space-y-6">
        <section class="panel p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="grid size-14 place-items-center rounded-lg bg-blue-50 text-xl font-black text-blue-700 dark:bg-blue-500/10 dark:text-blue-200">
                    {{ strtoupper(substr($member->first_name, 0, 1).substr($member->last_name, 0, 1)) }}
                </div>
                <x-badge :status="$member->status" />
            </div>
            <h2 class="mt-5 text-xl font-black text-slate-950 dark:text-white">{{ $member->full_name }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $member->member_code }}</p>
            <dl class="mt-6 space-y-4 text-sm">
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Email</dt>
                    <dd class="font-semibold">{{ $member->email }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Roll number</dt>
                    <dd class="font-semibold">{{ $member->roll_number }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Branch / year</dt>
                    <dd class="font-semibold">{{ $member->branch?->name ?: 'N/a' }} - {{ $member->year ?: 'N/a' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Loan quota</dt>
                    <dd class="font-semibold">{{ $member->books_borrowed_count }} of {{ $member->memberCategory->max_books }} books</dd>
                </div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('admin.members.edit', $member) }}" class="btn-secondary flex-1">Edit</a>
                @if (auth()->user()->isAdmin())
                    <button type="button" @click="$dispatch('open-modal', 'delete-member')" class="btn-danger">Hapus member</button>
                @endif
            </div>
        </section>

        @if ($member->status === \App\Models\Member::STATUS_PENDING)
            <section class="panel p-5">
                <h3 class="font-bold">Decision required</h3>
                <div class="mt-4 flex gap-3">
                    <button type="button" @click="$dispatch('open-modal', 'approve-member')" class="btn-primary">Approve</button>
                    <button type="button" @click="$dispatch('open-modal', 'reject-member')" class="btn-danger">Reject</button>
                </div>
            </section>
        @endif
    </aside>

    <section>
        <div class="panel overflow-hidden">
            <div class="border-b border-slate-200 p-5 dark:border-white/10">
                <h2 class="font-bold">Transaction history</h2>
            </div>
            @if ($transactions->isEmpty())
                <p class="p-6 text-sm text-slate-500 dark:text-slate-400">This member has no transactions.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="soft-table">
                        <thead>
                            <tr>
                                <th>Transaction</th>
                                <th>Book</th>
                                <th>Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                                <tr>
                                    <td>
                                        <a class="font-mono text-xs font-bold text-blue-700 hover:text-blue-900 dark:text-blue-300 dark:hover:text-blue-100" href="{{ route('admin.transactions.show', $transaction) }}">{{ $transaction->transaction_code }}</a>
                                    </td>
                                    <td class="font-semibold">
                                        {{ $transaction->bookCopy->book->title }}
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $transaction->bookCopy->copy_code }}</p>
                                    </td>
                                    <td>{{ $transaction->due_at->format('d M Y') }}</td>
                                    <td><x-badge :status="$transaction->display_status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        <div class="mt-6">{{ $transactions->links() }}</div>
    </section>
</div>

@if (auth()->user()->isAdmin())
    <x-danger-modal name="delete-member" title="Hapus member?" :action="route('admin.members.destroy', $member)" confirm-label="Hapus member">
        Hapus member {{ $member->full_name }}? Member hanya dapat dihapus jika tidak memiliki peminjaman aktif.
    </x-danger-modal>
@endif

@if ($member->status === \App\Models\Member::STATUS_PENDING)
    <x-modal name="approve-member" title="Approve member?">
        <p>{{ $member->full_name }} will receive borrowing access based on the {{ $member->memberCategory->name }} quota.</p>
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.members.approve', $member) }}">
                @csrf
                <button class="btn-primary">Approve</button>
            </form>
        </x-slot:actions>
    </x-modal>
    <x-modal name="reject-member" title="Reject member?">
        <p>This finalizes the registration as rejected. Reactivation requires a separate future workflow.</p>
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.members.reject', $member) }}">
                @csrf
                <button class="btn-danger">Reject</button>
            </form>
        </x-slot:actions>
    </x-modal>
@endif
@endsection
