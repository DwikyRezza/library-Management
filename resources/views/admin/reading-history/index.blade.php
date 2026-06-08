@extends('layouts.admin')

@section('title', 'Riwayat baca')
@section('page-title', 'Riwayat baca digital')
@section('page-description', 'Aktivitas membaca yang hanya dapat dilihat admin')

@section('content')
<form method="GET" class="panel mb-6 grid gap-3 p-4 md:grid-cols-[1fr_180px_180px_auto]">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Nama, kode member, email, judul, author">
    <input class="input" type="date" name="from" value="{{ request('from') }}" aria-label="Dari tanggal">
    <input class="input" type="date" name="to" value="{{ request('to') }}" aria-label="Sampai tanggal">
    <button class="btn-primary">Filter</button>
</form>

<div class="panel overflow-hidden">
    @if ($sessions->isEmpty())
        <x-empty-state title="Belum ada aktivitas membaca" message="Sesi akan muncul ketika member membuka buku digital." />
    @else
        <div class="overflow-x-auto">
            <table class="soft-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Buku</th>
                        <th>Aktivitas</th>
                        <th>Halaman</th>
                        <th>Durasi</th>
                        <th class="text-right">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $session)
                        <tr>
                            <td>
                                <p class="font-bold">{{ $session->member->full_name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $session->member->member_code }} - {{ $session->member->email }}</p>
                            </td>
                            <td>
                                <p class="font-semibold">{{ $session->book->title }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $session->book->author }}</p>
                            </td>
                            <td class="text-xs">
                                <p>Mulai {{ $session->started_at->format('d M Y H:i') }}</p>
                                <p class="text-slate-500 dark:text-slate-400">Aktif {{ $session->last_active_at->diffForHumans() }}</p>
                            </td>
                            <td>{{ $session->last_page }} / {{ $session->digital_book_asset_id ? $session->max_page : '-' }}</td>
                            <td>{{ gmdate('H:i:s', $session->duration_seconds) }}</td>
                            <td class="text-right"><a class="subtle-link" href="{{ route('admin.reading-history.show', $session) }}">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4 dark:border-white/10">{{ $sessions->links() }}</div>
    @endif
</div>
@endsection
