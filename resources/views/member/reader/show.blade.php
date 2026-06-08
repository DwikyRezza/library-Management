<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->title }} - LibraFlow Reader</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen select-none bg-slate-900 text-slate-100"
      x-data="{
          page: {{ $session->last_page }},
          total: {{ $asset->page_count }},
          zoom: 100,
          loading: true,
          loadError: false,
          heartbeatTimer: null,
          pageUrl() {
              let baseUrl = '{{ route("member.reader.page", [$session, "9999"]) }}';
              return baseUrl.replace('9999', this.page);
          },
          changePage(next) {
              if (next < 1 || next > this.total) return;
              this.page = next;
              this.loading = true;
              this.loadError = false;
              this.heartbeat();
              window.scrollTo({ top: 0, behavior: 'smooth' });
          },
          heartbeat(finish = false) {
              let url = finish ? '{{ route("member.reader.finish", $session) }}' : '{{ route("member.reader.heartbeat", $session) }}';

              fetch(url, {
                  method: 'POST',
                  keepalive: finish,
                  headers: {
                      'Content-Type': 'application/json',
                      'Accept': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                  },
                  body: JSON.stringify({ page: this.page }),
              });
          },
          init() {
              this.heartbeatTimer = setInterval(() => this.heartbeat(), 15000);
              window.addEventListener('beforeunload', () => this.heartbeat(true));
          }
      }"
      x-on:contextmenu.prevent
      x-on:copy.prevent
      x-on:cut.prevent
      x-on:dragstart.prevent
      x-on:keydown.window="
          if ((event.ctrlKey || event.metaKey) && ['s', 'p', 'u', 'c'].includes(event.key.toLowerCase())) event.preventDefault();
          if (event.key === 'ArrowLeft') changePage(page - 1);
          if (event.key === 'ArrowRight') changePage(page + 1);
      ">
    <header class="sticky top-0 z-30 border-b border-white/10 bg-slate-900/95 px-4 py-3 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate font-bold">{{ $book->title }}</p>
                <p class="text-xs text-slate-400">{{ auth('member')->user()->full_name }} - {{ auth('member')->user()->member_code }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button class="rounded-lg border border-white/10 px-3 py-2 text-sm hover:bg-white/5" x-on:click="zoom = Math.max(60, zoom - 10)">-</button>
                <span class="w-12 text-center text-xs" x-text="zoom + '%'"></span>
                <button class="rounded-lg border border-white/10 px-3 py-2 text-sm hover:bg-white/5" x-on:click="zoom = Math.min(180, zoom + 10)">+</button>
                <a href="{{ route('books.search') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold hover:bg-blue-500">Tutup</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-3 py-6">
        <div class="mb-4 rounded-lg border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-center text-xs text-amber-100">
            Halaman diberi watermark identitas dan sesi baca tercatat. Web tidak dapat menjamin pemblokiran screenshot perangkat.
        </div>

        <div class="relative mx-auto overflow-auto rounded-lg bg-slate-800 p-2 text-center shadow-2xl shadow-slate-950/30">
            <div x-show="loading" class="absolute inset-0 z-10 grid min-h-96 place-items-center bg-slate-800/80 font-semibold">Memuat halaman...</div>
            <div x-cloak x-show="loadError" class="grid min-h-96 place-items-center rounded-md border border-rose-400/20 bg-rose-400/10 px-6 py-12 text-center">
                <div>
                    <p class="text-lg font-bold text-rose-100">Halaman gagal dimuat</p>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-rose-100/80">File halaman hasil render tidak ditemukan atau server gagal membuat watermark. Hubungi admin untuk render ulang buku digital ini.</p>
                    <button type="button" class="mt-5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold hover:bg-blue-500" x-on:click="loading = true; loadError = false; $nextTick(() => { const image = $refs.pageImage; image.src = pageUrl() + '?retry=' + Date.now(); })">Coba muat ulang</button>
                </div>
            </div>
            <img :src="pageUrl()"
                 x-ref="pageImage"
                 :style="`width: ${zoom}%; max-width: none;`"
                 x-on:load="loading = false; loadError = false"
                 x-on:error="loading = false; loadError = true"
                 alt="Halaman buku"
                 draggable="false"
                 class="mx-auto block h-auto min-w-[320px]"
                 x-bind:class="loadError ? 'hidden' : ''">
        </div>
    </main>

    <footer class="sticky bottom-0 border-t border-white/10 bg-slate-900/95 px-4 py-3 backdrop-blur">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-4">
            <button class="rounded-lg border border-white/10 px-4 py-2 font-bold hover:bg-white/5 disabled:opacity-40" x-on:click="changePage(page - 1)" :disabled="page <= 1">Sebelumnya</button>
            <span class="text-sm font-semibold">Halaman <span x-text="page"></span> / {{ $asset->page_count }}</span>
            <button class="rounded-lg bg-blue-600 px-4 py-2 font-bold hover:bg-blue-500 disabled:opacity-40" x-on:click="changePage(page + 1)" :disabled="page >= total">Berikutnya</button>
        </div>
    </footer>
</body>
</html>
