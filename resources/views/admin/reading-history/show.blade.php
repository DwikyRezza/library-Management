@extends('layouts.admin')

@section('title', 'Detail sesi baca')
@section('page-title', 'Detail sesi baca')
@section('page-description', $session->book->title)

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.reading-history.index') }}" class="text-sm font-bold text-indigo-600">&larr; Kembali ke riwayat</a>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="panel p-6">
        <h2 class="text-lg font-black">Member dan buku</h2>
        <dl class="mt-5 grid gap-4 text-sm">
            <div><dt class="text-slate-500">Member</dt><dd class="font-bold">{{ $session->member->full_name }} ({{ $session->member->member_code }})</dd></div>
            <div><dt class="text-slate-500">Email</dt><dd>{{ $session->member->email }}</dd></div>
            <div><dt class="text-slate-500">Buku</dt><dd class="font-bold">{{ $session->book->title }}</dd></div>
            <div><dt class="text-slate-500">Author</dt><dd>{{ $session->book->author }}</dd></div>
        </dl>
    </section>

    <section class="panel p-6">
        <h2 class="text-lg font-black">Aktivitas</h2>
        <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Mulai</dt><dd class="font-semibold">{{ $session->started_at->format('d M Y H:i:s') }}</dd></div>
            <div><dt class="text-slate-500">Terakhir aktif</dt><dd class="font-semibold">{{ $session->last_active_at->format('d M Y H:i:s') }}</dd></div>
            <div><dt class="text-slate-500">Halaman terakhir</dt><dd class="font-semibold">{{ $session->last_page }}</dd></div>
            <div><dt class="text-slate-500">Halaman terjauh</dt><dd class="font-semibold">{{ $session->max_page }}</dd></div>
            <div><dt class="text-slate-500">Durasi (detik)</dt><dd class="font-semibold">{{ $session->duration_seconds }}</dd></div>
            <div><dt class="text-slate-500">Selesai</dt><dd class="font-semibold">{{ $session->ended_at?->format('d M Y H:i:s') ?: 'Belum ditutup' }}</dd></div>
        </dl>
    </section>

    <section class="panel p-6 lg:col-span-2">
        <h2 class="text-lg font-black">Perangkat dan jaringan</h2>
        <dl class="mt-5 grid gap-4 text-sm md:grid-cols-[220px_1fr]">
            <div><dt class="text-slate-500">IP address</dt><dd class="font-mono">{{ $session->ip_address ?: '-' }}</dd></div>
            <div><dt class="text-slate-500">User agent</dt><dd class="break-all font-mono text-xs">{{ $session->user_agent ?: '-' }}</dd></div>
        </dl>
    </section>
</div>
@endsection
