@extends('layouts.app')

@section('title', 'Borrowed - Lyrary')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-kicker">Member library</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">Borrowed</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Maksimal tiga pinjaman digital aktif, masing-masing selama 10 hari.</p>
        </div>
        <a href="{{ route('books.search') }}" class="btn-secondary self-start">Find books</a>
    </div>

    <div class="mt-8 flex items-center justify-between">
        <h2 class="text-xl font-black text-slate-950 dark:text-white">Pinjaman aktif</h2>
        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $activeLoans->count() }}/3 buku</span>
    </div>

    @if ($activeLoans->isEmpty())
        <div class="mt-4">
            <x-empty-state title="Belum ada pinjaman aktif" message="Pinjam buku digital dari katalog untuk mulai membaca." />
        </div>
    @else
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            @foreach ($activeLoans as $loan)
                <article class="panel p-5">
                    <div class="flex items-start gap-4">
                        <div class="book-cover">
                            <span class="text-[9px] font-black uppercase text-blue-500 dark:text-blue-200">Book</span>
                            <span class="line-clamp-4 text-xs font-black leading-tight">{{ $loan->book->title }}</span>
                            <span class="truncate text-[10px] text-blue-700/70 dark:text-blue-100/70">{{ $loan->book->author }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="line-clamp-2 text-lg font-bold text-slate-950 dark:text-white">{{ $loan->book->title }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $loan->book->author }}</p>
                            <p class="mt-4 text-xs font-bold uppercase text-slate-400">Due date</p>
                            <p class="mt-1 text-sm font-semibold">{{ $loan->due_at->translatedFormat('d M Y, H:i') }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('member.reader.open', $loan->book) }}" class="btn-primary">
                                    {{ $loan->last_read_page > 1 ? "Lanjutkan Membaca (Hal. {$loan->last_read_page})" : 'Read' }}
                                </a>
                                @if ($loan->can_extend)
                                    <form method="POST" action="{{ route('member.borrowed.extend', $loan) }}">
                                        @csrf
                                        <button class="btn-secondary">Extend 10 days</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('member.borrowed.return', $loan) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ghost text-rose-600 dark:text-rose-300">Return</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div class="mt-12">
        <h2 class="text-xl font-black text-slate-950 dark:text-white">Riwayat pinjaman</h2>
        @if ($loanHistory->isEmpty())
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Belum ada riwayat pengembalian.</p>
        @else
            <div class="panel mt-4 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="soft-table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Borrowed</th>
                                <th>Returned</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($loanHistory as $loan)
                                <tr>
                                    <td>
                                        <p class="font-bold text-slate-900 dark:text-white">{{ $loan->book->title }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $loan->book->author }}</p>
                                    </td>
                                    <td>{{ $loan->borrowed_at->translatedFormat('d M Y') }}</td>
                                    <td>{{ $loan->returned_at->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="capitalize">{{ $loan->return_reason === \App\Models\DigitalLoan::RETURN_EXPIRED ? 'Automatic' : 'Manual' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6">{{ $loanHistory->links() }}</div>
        @endif
    </div>
</section>
@endsection
