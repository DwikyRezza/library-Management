<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->title }} - LibraFlow Reader</title>
    @vite(['resources/css/app.css', 'resources/js/reader.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
    <div
        data-pdf-reader
        data-document-url="{{ route('member.reader.document', $session) }}"
        data-heartbeat-url="{{ route('member.reader.heartbeat', $session) }}"
        data-finish-url="{{ route('member.reader.finish', $session) }}"
        data-highlight-store-url="{{ route('member.reader.highlights.store') }}"
        data-highlight-delete-url-base="{{ url('/member/reader/highlight') }}"
        data-digital-loan-id="{{ $loan->id }}"
        data-initial-page="{{ $initialPage }}"
        class="flex min-h-screen flex-col"
    >
        <header class="sticky top-0 z-30 border-b border-slate-700 bg-slate-900 px-3 py-3 text-slate-100 shadow-sm">
            <div class="mx-auto flex max-w-screen-2xl flex-wrap items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-sm font-bold sm:text-base">{{ $book->title }}</h1>
                    <p class="truncate text-xs text-slate-400">
                        {{ auth('member')->user()->full_name }} - {{ auth('member')->user()->member_code }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span id="readerSaveStatus" class="hidden text-xs font-semibold text-slate-400 sm:inline" aria-live="polite"></span>
                    <button id="readerZoomOut" type="button" aria-label="Perkecil halaman" title="Perkecil"
                            class="grid size-10 place-items-center rounded-lg border border-slate-600 font-bold hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">
                        -
                    </button>
                    <span id="readerZoom" class="w-12 text-center text-xs font-semibold">100%</span>
                    <button id="readerZoomIn" type="button" aria-label="Perbesar halaman" title="Perbesar"
                            class="grid size-10 place-items-center rounded-lg border border-slate-600 font-bold hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">
                        +
                    </button>
                    <a href="{{ route('books.search') }}"
                       class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold hover:bg-blue-500">
                        Tutup
                    </a>
                </div>
            </div>
        </header>

        <main class="flex flex-1 p-2 sm:p-4">
            <div id="readerStage"
                 class="relative mx-auto flex min-h-[70vh] w-full max-w-screen-2xl items-start overflow-auto rounded-lg border border-slate-300 bg-slate-800 p-3 shadow-lg sm:p-5">
                <div id="readerLoading"
                     class="absolute inset-0 z-10 grid min-h-96 place-items-center bg-slate-800 text-sm font-semibold text-slate-100">
                    Memuat dokumen...
                </div>

                <div id="readerError"
                     class="hidden min-h-[70vh] place-items-center rounded-md border border-rose-300 bg-rose-50 px-6 py-12 text-center">
                    <div>
                        <p class="text-lg font-bold text-rose-800">Dokumen gagal dimuat</p>
                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-rose-700">
                            PDF tidak dapat dibaca dari penyimpanan. Coba muat ulang atau hubungi admin jika masalah tetap terjadi.
                        </p>
                        <button id="readerRetry" type="button"
                                class="mt-5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-500">
                            Coba muat ulang
                        </button>
                    </div>
                </div>

                <div id="readerPageSurface" class="reader-page-surface invisible">
                    <canvas id="readerCanvas"
                            class="block max-w-none bg-white"
                            aria-label="Halaman buku"></canvas>
                    <div id="readerHighlightLayer" class="reader-highlight-layer" aria-hidden="true"></div>
                    <div id="readerTextLayer" class="reader-text-layer textLayer" aria-label="Teks halaman buku"></div>
                </div>
            </div>
        </main>

        <div id="readerHighlightPopover"
             class="reader-highlight-popover hidden"
             role="toolbar"
             aria-label="Pilih warna stabilo">
            <button type="button" class="reader-color-swatch bg-[#fef08a]" data-highlight-color="#fef08a"
                    aria-label="Stabilo kuning" title="Kuning"></button>
            <button type="button" class="reader-color-swatch bg-[#bbf7d0]" data-highlight-color="#bbf7d0"
                    aria-label="Stabilo hijau" title="Hijau"></button>
            <button type="button" class="reader-color-swatch bg-[#bfdbfe]" data-highlight-color="#bfdbfe"
                    aria-label="Stabilo biru" title="Biru"></button>
        </div>

        <script id="readerHighlightsData" type="application/json">{!! \Illuminate\Support\Js::encode($highlights) !!}</script>

        <footer class="sticky bottom-0 z-30 border-t border-slate-700 bg-slate-900 px-3 py-3 text-slate-100 shadow-sm">
            <div class="mx-auto flex max-w-xl items-center justify-between gap-3">
                <button id="readerPrevious" type="button"
                        class="rounded-lg border border-slate-600 px-3 py-2.5 text-sm font-bold hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40 sm:px-5">
                    Sebelumnya
                </button>
                <span class="whitespace-nowrap text-xs font-semibold sm:text-sm">
                    Halaman <span id="readerPage">1</span> / <span id="readerTotal">-</span>
                </span>
                <button id="readerNext" type="button"
                        class="rounded-lg bg-blue-600 px-3 py-2.5 text-sm font-bold hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-40 sm:px-5">
                    Berikutnya
                </button>
            </div>
        </footer>
    </div>
</body>
</html>
