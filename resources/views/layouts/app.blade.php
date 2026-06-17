<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="MhFD60EegEXVl4JXdyeVrp_jVi5OuUl-Bkgn50w5p9U" />
    <link rel="icon" type="image/png" href="{{ asset('images/logo-libraflow.png') }}">
    
    <!-- Judul Dinamis dengan Default Kaya Kata Kunci SEO -->
    <title>@yield('title', 'Lyrary - Platform Perpustakaan & E-Reader Digital Modern')</title>

    <!-- Meta Tags Utama untuk Google Indexing & Target Pembaca Luas -->
    <meta name="description" content="Lyrary adalah platform perpustakaan digital cerdas untuk membaca, mengelola, dan mengakses koleksi buku digital secara aman dengan fitur e-reader premium. Developed by Muhammad Dwiky Yanuarezza.">
    <meta name="keywords" content="Lyrary, Lyrary Sytes, library, digital library, perpustakaan digital, e-reader pdf, baca buku online, platform baca buku, library online, tempat baca pdf, baca novel online, download buku pdf, jurnal ilmiah mahasiswa, pinjam buku digital, e-book reader free, baca buku gratis, referensi tugas besar IT, manajemen buku kuliah, cara baca PDF di browser, platform e-reader web, sistem perpustakaan Laravel, baca dokumentasi coding, repository buku digital, Muhammad Dwiky Yanuarezza, Telkom University Surabaya">
    <meta name="author" content="Muhammad Dwiky Yanuarezza">

    <!-- Open Graph / Facebook (Preview saat link dibagikan di WhatsApp/Media Sosial) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://lyrary.sytes.net/">
    <meta property="og:title" content="Lyrary - Platform Perpustakaan & E-Reader Digital">
    <meta property="og:description" content="Akses koleksi buku digital premium kamu di Lyrary. Desain modern, cepat, dan aman.">
    <meta property="og:image" content="{{ asset('images/logo-libraflow.png') }}">

    <!-- Twitter Card -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://lyrary.sytes.net/">
    <meta property="twitter:title" content="Lyrary - Platform Perpustakaan & E-Reader Digital">
    <meta property="twitter:description" content="Akses koleksi buku digital premium kamu di Lyrary.">
    <meta property="twitter:image" content="{{ asset('images/logo-libraflow.png') }}">

    <!-- Google Site Name Schema (Solusi membuang text "No-IP" di Google) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Lyrary",
      "url": "https://lyrary.sytes.net/",
      "description": "Platform Perpustakaan & E-Reader Digital Modern"
    }
    </script>

    <script>
        (() => {
            const saved = localStorage.getItem('lyrary-theme');
            const dark = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html.dark .pub-header { background-color: rgba(16, 25, 39, 0.88); border-color: rgba(255,255,255,0.10); }
        html.dark .pub-footer { background-color: rgba(15, 23, 36, 0.72); border-color: rgba(255,255,255,0.10); }
    </style>
</head>
<body class="min-h-screen text-slate-900 dark:text-slate-100" x-data="appShell">
    <header class="pub-header sticky top-0 z-30 border-b border-slate-200/80 bg-white/80 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo-libraflow.png') }}" alt="Lyrary" class="size-10 rounded-lg object-contain">
                <span>
                    <span class="block text-lg font-black text-slate-950 dark:text-slate-100">Lyrary</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Where Knowledge Flows Freely</span>
                </span>
            </a>
            <nav class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('books.search') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-950 sm:block dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Catalog</a>
                <a href="{{ route('book-categories.index') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-950 sm:block dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Categories</a>
                @auth('member')
                    <a href="{{ route('member.borrowed.index') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-950 sm:block dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Borrowed</a>
                @endauth
                @guest('member')
                    <a href="{{ route('member.register') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-950 sm:block dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">Membership</a>
                @endguest
                <button
                    type="button"
                    @click="toggleTheme()"
                    class="interactive-control grid size-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm dark:border-white/10 dark:bg-slate-900 dark:text-slate-200"
                    :aria-label="dark ? 'Gunakan tema terang' : 'Gunakan tema gelap'"
                    :title="dark ? 'Gunakan tema terang' : 'Gunakan tema gelap'"
                >
                    <i x-show="!dark" data-lucide="moon" class="size-5" aria-hidden="true"></i>
                    <i x-show="dark" data-lucide="sun" class="size-5" aria-hidden="true"></i>
                </button>
                @auth('member')
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button
                            @click="open = !open"
                            type="button"
                            class="interactive-control grid size-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm dark:border-white/10 dark:bg-slate-900 dark:text-slate-200"
                            aria-label="Buka menu profil"
                            title="Profil"
                        >
                            <i data-lucide="user-round" class="size-5" aria-hidden="true"></i>
                        </button>

                        <div x-cloak x-show="open" x-transition class="absolute right-0 z-50 mt-2 w-52 origin-top-right rounded-lg border border-slate-200 bg-white p-1 shadow-lg dark:border-white/10 dark:bg-slate-900">
                            <a href="{{ route('member.profile') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                <i data-lucide="user-round" class="size-4" aria-hidden="true"></i>
                                Profil
                            </a>
                            <a href="{{ route('member.booklist.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                <i data-lucide="bookmark" class="size-4" aria-hidden="true"></i>
                                Booklist
                            </a>
                            <a href="{{ route('member.borrowed.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                <i data-lucide="book-open-check" class="size-4" aria-hidden="true"></i>
                                Borrowed
                            </a>
                            <hr class="my-1 border-slate-100 dark:border-white/10" />
                            <form method="POST" action="{{ route('member.logout') }}" class="block">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10">
                                    <i data-lucide="log-out" class="size-4" aria-hidden="true"></i>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('member.login') }}" class="btn-primary">Login member</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="page-enter">
        <div class="mx-auto max-w-7xl px-4 pt-5 sm:px-6 lg:px-8">
            <x-alert />
        </div>
        @yield('content')
    </main>

    <footer class="pub-footer mt-16 border-t border-slate-200/80 bg-white/60">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-slate-400">
            <p>&copy; {{ date('Y') }} Lyrary. Where Knowledge Flows Freely.</p>
            <p>Discover. Borrow. Learn.</p>
        </div>
    </footer>
</body>
</html>