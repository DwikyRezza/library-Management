<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LibraFlow')</title>
    <script>
        (() => {
            const saved = localStorage.getItem('libraflow-theme');
            const dark = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100" x-data="appShell">
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-indigo-600 to-blue-500 font-black text-white shadow-lg shadow-indigo-500/20">L</span>
                <span>
                    <span class="block text-lg font-black tracking-tight">LibraFlow</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Modern Library System</span>
                </span>
            </a>
            <nav class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('books.search') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:block dark:text-slate-300 dark:hover:bg-slate-800">Catalog</a>
                <a href="{{ route('member.register') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:block dark:text-slate-300 dark:hover:bg-slate-800">Membership</a>
                <button type="button" @click="toggleTheme()" class="grid size-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" aria-label="Toggle color theme">
                    <span x-text="dark ? 'Light' : 'Dark'" class="text-[10px] font-bold"></span>
                </button>
                @auth('member')
                    <form method="POST" action="{{ route('member.logout') }}">
                        @csrf
                        <button class="btn-secondary">Keluar member</button>
                    </form>
                @elseauth
                    <a href="{{ route('admin.dashboard') }}" class="btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('member.login') }}" class="btn-primary">Login member</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <div class="mx-auto max-w-7xl px-4 pt-5 sm:px-6 lg:px-8">
            <x-alert />
        </div>
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-slate-400">
            <p>&copy; {{ date('Y') }} LibraFlow. Built for modern campus libraries.</p>
            <p>Discover. Borrow. Learn.</p>
        </div>
    </footer>
</body>
</html>
