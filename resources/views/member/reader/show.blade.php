<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->title }} - LibraFlow Reader</title>
    <script>
        window.libraFlowPdfConfig = Object.freeze({
            version: '6.0.227',
            wasmUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.0.227/wasm/',
            cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.0.227/cmaps/',
            standardFontDataUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.0.227/standard_fonts/',
        });
    </script>
    @vite(['resources/css/app.css', 'resources/js/reader.js'])
</head>
<body class="reader-body">
    <div
        data-pdf-reader
        data-document-url="{{ route('member.reader.document', $session) }}"
        data-heartbeat-url="{{ route('member.reader.heartbeat', $session) }}"
        data-finish-url="{{ route('member.reader.finish', $session) }}"
        data-annotation-index-url="{{ route('member.reader.annotations.index', $session) }}"
        data-annotation-store-url="{{ route('member.reader.annotations.store', $session) }}"
        data-highlight-store-url="{{ route('member.reader.highlights.store') }}"
        data-highlight-delete-url-base="{{ url('/member/reader/highlight') }}"
        data-digital-loan-id="{{ $loan->id }}"
        data-initial-page="{{ $initialPage }}"
        class="reader-shell"
    >
        <header class="reader-toolbar">
            <div class="reader-toolbar-scroll">
                <div class="reader-toolbar-brand">
                    <button id="readerToggleSidebar" type="button" class="reader-icon-button"
                            aria-label="Buka atau tutup sidebar" title="Sidebar">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                            <path d="M9 4v16"></path>
                        </svg>
                    </button>
                    <div class="reader-book-meta">
                        <strong>{{ $book->title }}</strong>
                        <span>{{ auth('member')->user()->full_name }}</span>
                    </div>
                </div>

                <div class="reader-toolbar-group" aria-label="Alat anotasi">
                    <button type="button" class="reader-tool-button" data-annotation-tool="highlighter"
                            aria-pressed="false" title="Stabilo">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m9 11-6 6v4h4l6-6"></path>
                            <path d="m14 4 6 6-8 8-6-6z"></path>
                        </svg>
                        <span>Stabilo</span>
                    </button>
                    <button type="button" class="reader-tool-button" data-annotation-tool="pen"
                            aria-pressed="false" title="Bulpoin">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m12 19 7-7 3 3-7 7-4 1z"></path>
                            <path d="m18 13-7-7 3-3 7 7"></path>
                        </svg>
                        <span>Pen</span>
                    </button>
                    <button type="button" class="reader-tool-button" data-annotation-tool="eraser"
                            aria-pressed="false" title="Penghapus">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m7 21-4-4L16 4l5 5L9 21z"></path>
                            <path d="m11 8 5 5"></path>
                            <path d="M7 21h14"></path>
                        </svg>
                        <span>Hapus</span>
                    </button>
                    <button type="button" class="reader-tool-button" data-annotation-tool="text"
                            aria-pressed="false" title="Tambah catatan teks">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 5h14"></path>
                            <path d="M12 5v14"></path>
                            <path d="M8 19h8"></path>
                        </svg>
                        <span>Teks</span>
                    </button>
                    <label class="reader-color-control" title="Warna anotasi">
                        <span>Warna</span>
                        <input id="readerAnnotationColor" type="color" value="#7c3aed"
                               aria-label="Warna anotasi">
                    </label>
                    <label class="reader-select-control" title="Ukuran brush">
                        <span>Ukuran</span>
                        <select id="readerBrushSize" aria-label="Ukuran brush">
                            <option value="2">2 px</option>
                            <option value="4" selected>4 px</option>
                            <option value="8">8 px</option>
                            <option value="14">14 px</option>
                            <option value="20">20 px</option>
                        </select>
                    </label>
                    <button id="readerUndo" type="button" class="reader-icon-button"
                            aria-label="Undo anotasi" title="Undo" disabled>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M9 7 4 12l5 5"></path>
                            <path d="M4 12h10a6 6 0 0 1 6 6"></path>
                        </svg>
                    </button>
                    <button id="readerRedo" type="button" class="reader-icon-button"
                            aria-label="Redo anotasi" title="Redo" disabled>
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m15 7 5 5-5 5"></path>
                            <path d="M20 12H10a6 6 0 0 0-6 6"></path>
                        </svg>
                    </button>
                </div>

                <div class="reader-toolbar-group">
                    <button id="readerZoomOut" type="button" class="reader-icon-button"
                            aria-label="Perkecil halaman" title="Perkecil">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12h12"></path></svg>
                    </button>
                    <span id="readerZoom" class="reader-zoom-label">100%</span>
                    <button id="readerZoomIn" type="button" class="reader-icon-button"
                            aria-label="Perbesar halaman" title="Perbesar">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6 12h12"></path><path d="M12 6v12"></path>
                        </svg>
                    </button>
                </div>

                <form id="readerSearchForm" class="reader-search" role="search">
                    <input id="readerSearchInput" type="search" placeholder="Cari dalam PDF..."
                           autocomplete="off" aria-label="Cari teks dalam PDF">
                    <button type="submit" class="reader-icon-button" aria-label="Cari" title="Cari">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path>
                        </svg>
                    </button>
                </form>

                <div class="reader-toolbar-group">
                    <span id="readerSaveStatus" class="reader-save-status" aria-live="polite"></span>
                    <button id="readerPrint" type="button" class="reader-icon-button"
                            aria-label="Cetak PDF" title="Cetak">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 8V3h10v5"></path><path d="M7 17H5a3 3 0 0 1-3-3v-3a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v3a3 3 0 0 1-3 3h-2"></path>
                            <path d="M7 14h10v7H7z"></path>
                        </svg>
                    </button>
                    <a id="readerDownload" class="reader-icon-button"
                       href="{{ route('member.reader.document', $session) }}"
                       download="{{ \Illuminate\Support\Str::slug($book->title) }}.pdf"
                       aria-label="Unduh PDF" title="Unduh">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path>
                        </svg>
                    </a>
                    <a href="{{ route('books.search') }}" class="reader-close-button">Tutup</a>
                </div>
            </div>
        </header>

        <div class="reader-layout">
            <button id="readerSidebarBackdrop" type="button" class="reader-sidebar-backdrop"
                    aria-label="Tutup sidebar"></button>

            <aside id="readerSidebar" class="reader-sidebar" aria-label="Thumbnail halaman">
                <div class="reader-sidebar-header">
                    <div>
                        <strong>Halaman</strong>
                        <span id="readerSidebarCount">Memuat...</span>
                    </div>
                    <button id="readerCloseSidebar" type="button" class="reader-icon-button reader-mobile-only"
                            aria-label="Tutup sidebar">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m6 6 12 12"></path><path d="m18 6-12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="readerThumbnailList" class="reader-thumbnail-list"></div>
            </aside>

            <main id="readerStage" class="reader-workspace">
                <div id="readerLoading"
                     class="reader-document-loading">
                    Memuat dokumen...
                </div>

                <div id="readerError" class="reader-document-error hidden">
                    <div>
                        <p>Dokumen gagal dimuat</p>
                        <span>
                            PDF tidak dapat dibaca dari penyimpanan. Coba muat ulang atau hubungi admin jika masalah tetap terjadi.
                        </span>
                        <button id="readerRetry" type="button">
                            Coba muat ulang
                        </button>
                    </div>
                </div>

                <div id="readerPages" class="reader-pages invisible" aria-live="off"></div>

                <template id="readerPageTemplate">
                    <article class="reader-page-frame" data-reader-page>
                        <div class="reader-page-surface" data-page-surface>
                            <div class="reader-page-skeleton" data-page-skeleton role="status">
                                <div class="reader-page-skeleton-lines" aria-hidden="true"></div>
                                <span data-page-loading-text>Memuat halaman...</span>
                            </div>
                            <canvas class="reader-page-canvas" data-page-canvas></canvas>
                            <div class="reader-highlight-layer" data-page-highlight-layer aria-hidden="true"></div>
                            <canvas class="reader-annotation-layer" data-page-annotation-layer></canvas>
                            <div class="reader-text-layer textLayer" data-page-text-layer></div>
                            <div class="reader-page-error hidden" data-page-error>
                                <p>Halaman gagal dirender.</p>
                                <button type="button" data-page-retry>Coba lagi</button>
                            </div>
                        </div>
                        <p class="reader-page-number" data-page-number></p>
                    </article>
                </template>

                <div id="readerFloatingControls" class="reader-floating-controls">
                    <button id="readerPrevious" type="button" class="reader-icon-button"
                            aria-label="Halaman sebelumnya" title="Sebelumnya">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>
                    <span>Halaman <strong id="readerPage">1</strong> dari <strong id="readerTotal">-</strong></span>
                    <button id="readerNext" type="button" class="reader-icon-button"
                            aria-label="Halaman berikutnya" title="Berikutnya">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                    </button>
                    <span class="reader-floating-divider"></span>
                    <button id="readerFloatingZoomOut" type="button" class="reader-icon-button"
                            aria-label="Perkecil halaman" title="Perkecil">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12h12"></path></svg>
                    </button>
                    <button id="readerFloatingZoomIn" type="button" class="reader-icon-button"
                            aria-label="Perbesar halaman" title="Perbesar">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M6 12h12"></path><path d="M12 6v12"></path>
                        </svg>
                    </button>
                    <button id="readerFullscreen" type="button" class="reader-icon-button"
                            aria-label="Layar penuh" title="Layar penuh">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8 3H3v5"></path><path d="M16 3h5v5"></path>
                            <path d="M8 21H3v-5"></path><path d="M16 21h5v-5"></path>
                        </svg>
                    </button>
                </div>
            </main>
        </div>

        <div id="readerRenderStatus" class="reader-render-status hidden" role="status" aria-live="polite"></div>

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

        <dialog id="readerTextNoteDialog" class="reader-text-dialog">
            <form method="dialog">
                <div>
                    <strong>Tambah catatan teks</strong>
                    <button value="cancel" class="reader-icon-button" aria-label="Tutup">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m6 6 12 12"></path><path d="m18 6-12 12"></path>
                        </svg>
                    </button>
                </div>
                <textarea id="readerTextNoteContent" maxlength="2000"
                          placeholder="Tulis catatan untuk halaman ini..."></textarea>
                <menu>
                    <button value="cancel" class="reader-dialog-secondary">Batal</button>
                    <button id="readerSaveTextNote" value="default" class="reader-dialog-primary">Simpan</button>
                </menu>
            </form>
        </dialog>
    </div>
</body>
</html>
