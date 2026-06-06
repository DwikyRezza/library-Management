<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $book->title }} - LibraFlow Reader</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen select-none bg-slate-950 text-slate-100"
      x-data="{
          page: {{ $session->last_page }},
          total: {{ $asset->page_count }},
          zoom: 100,
          loading: true,
          heartbeatTimer: null,
          pageUrl() {
              return @js(route('member.reader.page', [$session, '__PAGE__'])).replace('__PAGE__', this.page);
          },
          changePage(next) {
              if (next < 1 || next > this.total) return;
              this.page = next;
              this.loading = true;
              this.heartbeat();
              window.scrollTo({ top: 0, behavior: 'smooth' });
          },
          heartbeat(finish = false) {
              fetch(finish ? @js(route('member.reader.finish', $session)) : @js(route('member.reader.heartbeat', $session)), {
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
      @contextmenu.prevent
      @copy.prevent
      @cut.prevent
      @dragstart.prevent
      @keydown.window="
          if ((event.ctrlKey || event.metaKey) && ['s', 'p', 'u', 'c'].includes(event.key.toLowerCase())) event.preventDefault();
          if (event.key === 'ArrowLeft') changePage(page - 1);
          if (event.key === 'ArrowRight') changePage(page + 1);
      ">
    <header class="sticky top-0 z-30 border-b border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate font-bold">{{ $book->title }}</p>
                <p class="text-xs text-slate-400">{{ auth('member')->user()->full_name }} · {{ auth('member')->user()->member_code }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button class="rounded-lg border border-slate-700 px-3 py-2 text-sm" @click="zoom = Math.max(60, zoom - 10)">-</button>
                <span class="w-12 text-center text-xs" x-text="zoom + '%'"></span>
                <button class="rounded-lg border border-slate-700 px-3 py-2 text-sm" @click="zoom = Math.min(180, zoom + 10)">+</button>
                <a href="{{ route('books.search') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold">Tutup</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-3 py-6">
        <div class="mb-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-center text-xs text-amber-200">
            Halaman diberi watermark identitas dan sesi baca tercatat. Web tidak dapat menjamin pemblokiran screenshot perangkat.
        </div>

        <div class="relative mx-auto overflow-auto rounded-xl bg-slate-900 p-2 text-center shadow-2xl">
            <div x-show="loading" class="absolute inset-0 z-10 grid min-h-96 place-items-center bg-slate-900/80 font-semibold">Memuat halaman...</div>
            <img :src="pageUrl()"
                 :style="`width: ${zoom}%; max-width: none;`"
                 @load="loading = false"
                 @error="loading = false"
                 alt="Halaman buku"
                 draggable="false"
                 class="mx-auto block h-auto min-w-[320px]">
        </div>
    </main>

    <footer class="sticky bottom-0 border-t border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-4">
            <button class="rounded-xl border border-slate-700 px-4 py-2 font-bold disabled:opacity-40" @click="changePage(page - 1)" :disabled="page <= 1">Sebelumnya</button>
            <span class="text-sm font-semibold">Halaman <span x-text="page"></span> / {{ $asset->page_count }}</span>
            <button class="rounded-xl bg-indigo-600 px-4 py-2 font-bold disabled:opacity-40" @click="changePage(page + 1)" :disabled="page >= total">Berikutnya</button>
        </div>
    </footer>
</body>
</html>
