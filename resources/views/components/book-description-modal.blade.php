@props(['book', 'name'])

<x-modal :name="$name" :title="$book->title">
    <dl class="grid grid-cols-[90px_1fr] gap-x-3 gap-y-2 text-sm">
        <dt class="font-semibold text-slate-500 dark:text-slate-400">Author</dt>
        <dd>{{ $book->author }}</dd>
        <dt class="font-semibold text-slate-500 dark:text-slate-400">Category</dt>
        <dd>{{ $book->category->name }}</dd>
        <dt class="font-semibold text-slate-500 dark:text-slate-400">Synopsis</dt>
        <dd class="leading-6">{{ $book->description ?: 'Deskripsi buku belum tersedia.' }}</dd>
    </dl>
</x-modal>
