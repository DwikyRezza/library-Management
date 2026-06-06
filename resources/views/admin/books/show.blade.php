@extends('layouts.admin')
@section('title', $book->title)
@section('page-title', 'Book detail')
@section('page-description', 'Metadata, physical copies, and recent circulation')
@section('content')
<div class="grid gap-6 xl:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        <section class="panel p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row">
                <div><x-badge :status="$book->available_copies > 0 ? 'available' : 'unavailable'" /><h2 class="mt-3 text-2xl font-black">{{ $book->title }}</h2><p class="mt-1 text-slate-500">{{ $book->author }}</p></div>
                <a href="{{ route('admin.books.edit', $book) }}" class="btn-secondary self-start">Edit metadata</a>
            </div>
            <dl class="mt-6 grid gap-4 border-t border-slate-200 pt-5 text-sm sm:grid-cols-3 dark:border-slate-800">
                <div><dt class="text-slate-500">Category</dt><dd class="mt-1 font-semibold">{{ $book->category->name }}</dd></div>
                <div><dt class="text-slate-500">Publisher / year</dt><dd class="mt-1 font-semibold">{{ $book->publisher ?: 'N/a' }} · {{ $book->publication_year ?: 'N/a' }}</dd></div>
                <div><dt class="text-slate-500">ISBN</dt><dd class="mt-1 font-semibold">{{ $book->isbn ?: 'N/a' }}</dd></div>
            </dl>
            @if ($book->description)<p class="mt-5 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $book->description }}</p>@endif
        </section>
        <section class="panel overflow-hidden">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Physical copies ({{ $book->total_copies }})</h2></div>
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-900"><tr><th class="px-5 py-3">Copy code</th><th class="px-5 py-3">Shelf</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Condition</th><th class="px-5 py-3 text-right">Update</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">@foreach ($book->copies as $copy)<tr><td class="px-5 py-4 font-mono text-xs font-bold">{{ $copy->copy_code }}</td><td class="px-5 py-4">{{ $copy->shelf_location ?: 'N/a' }}</td><td class="px-5 py-4"><x-badge :status="$copy->status" /></td><td class="px-5 py-4 text-slate-500">{{ $copy->condition_note ?: 'No note' }}</td><td class="px-5 py-4 text-right">
                    @if ($copy->status !== \App\Models\BookCopy::STATUS_BORROWED)
                        <button @click="$dispatch('open-modal', 'copy-{{ $copy->id }}')" class="font-semibold text-indigo-600">Change</button>
                        <x-modal name="copy-{{ $copy->id }}" title="Update {{ $copy->copy_code }}">
                            <form id="copy-form-{{ $copy->id }}" method="POST" action="{{ route('admin.book-copies.update', $copy) }}" class="space-y-4">@csrf @method('PATCH')<div><label class="label">Status</label><select class="input" name="status">@foreach (['available', 'maintenance', 'lost'] as $status)<option value="{{ $status }}" @selected($copy->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div><label class="label">Condition note</label><textarea class="input" name="condition_note">{{ $copy->condition_note }}</textarea></div></form>
                            <x-slot:actions><button form="copy-form-{{ $copy->id }}" class="btn-primary">Save</button></x-slot:actions>
                        </x-modal>
                    @else
                        <span class="text-xs text-slate-400">Active loan</span>
                    @endif
                </td></tr>@endforeach</tbody>
            </table></div>
        </section>
        <section class="panel overflow-hidden">
            <div class="border-b border-slate-200 p-5 dark:border-slate-800"><h2 class="font-bold">Recent transactions</h2></div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">@forelse ($recentTransactions as $transaction)<a href="{{ route('admin.transactions.show', $transaction) }}" class="flex items-center justify-between p-5 hover:bg-slate-50 dark:hover:bg-slate-800"><div><p class="font-semibold">{{ $transaction->member->full_name }}</p><p class="text-xs text-slate-500">{{ $transaction->transaction_code }} · {{ $transaction->bookCopy->copy_code }}</p></div><x-badge :status="$transaction->display_status" /></a>@empty<p class="p-5 text-sm text-slate-500">No transactions for this book.</p>@endforelse</div>
        </section>
    </div>
    <aside class="space-y-6">
        <form method="POST" action="{{ route('admin.books.copies.store', $book) }}" class="panel p-5">@csrf<h2 class="font-bold">Add physical copies</h2><p class="mt-1 text-sm text-slate-500">Existing copies remain unchanged.</p><div class="mt-5"><label class="label">Number of copies</label><input class="input" type="number" min="1" max="200" name="number_of_copies" value="1" required></div><div class="mt-4"><label class="label">Shelf location</label><input class="input" name="shelf_location" placeholder="Example: A-12"></div><button class="btn-primary mt-5 w-full">Generate copies</button></form>
        @if (auth()->user()->isAdmin())
            <section class="panel p-5">
                <h2 class="font-bold">Kelola buku digital</h2>
                <p class="mt-1 text-sm text-slate-500">PDF asli disimpan privat dan dirender menjadi gambar halaman.</p>

                @if ($book->digitalAsset)
                    <div class="mt-5 rounded-xl border border-slate-200 p-4 text-sm dark:border-slate-700">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-bold capitalize">{{ $book->digitalAsset->status }}</span>
                            <span class="text-xs text-slate-500">{{ number_format($book->digitalAsset->file_size / 1024, 1) }} KB</span>
                        </div>
                        <p class="mt-2 break-all text-xs text-slate-500">{{ $book->digitalAsset->original_name }}</p>
                        @if ($book->digitalAsset->isReady())
                            <p class="mt-2 text-xs text-emerald-600">{{ $book->digitalAsset->page_count }} halaman siap dibaca.</p>
                        @elseif ($book->digitalAsset->status === \App\Models\DigitalBookAsset::STATUS_FAILED)
                            <p class="mt-2 break-words text-xs text-red-600">{{ $book->digitalAsset->last_error }}</p>
                        @else
                            <p class="mt-2 text-xs text-amber-600">Menunggu atau sedang diproses oleh queue worker.</p>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.books.digital.store', $book) }}" enctype="multipart/form-data" class="mt-5">
                    @csrf
                    <label class="label">{{ $book->digitalAsset ? 'Ganti file PDF' : 'Upload file PDF' }}</label>
                    <input class="input" type="file" name="pdf" accept="application/pdf,.pdf" required>
                    <x-field-error name="pdf" />
                    <button class="btn-primary mt-4 w-full">{{ $book->digitalAsset ? 'Ganti dan render ulang' : 'Upload dan render' }}</button>
                </form>

                @if ($book->digitalAsset)
                    <form method="POST" action="{{ route('admin.books.digital.destroy', [$book, $book->digitalAsset]) }}" class="mt-3" onsubmit="return confirm('Hapus buku digital dan semua gambar privatnya?')">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-xl border border-red-300 px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950">Hapus buku digital</button>
                    </form>
                @endif
            </section>
        @endif
    </aside>
</div>
@endsection
