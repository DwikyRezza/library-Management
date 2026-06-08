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
    <style>
        /* Guaranteed dark mode overrides for public layout */
        html.dark body            { background-color: #051424; color: #d4e4fa; }
        html.dark .pub-header     { background-color: rgba(5,20,36,0.95); border-color: rgba(69,71,76,0.15); }
        html.dark .pub-header a,
        html.dark .pub-header span { color: #c6c6cc; }
        html.dark .pub-header a:hover { color: #d4e4fa; background-color: #1c2b3c; }
        html.dark .pub-footer     { background-color: #0d1c2d; border-color: rgba(69,71,76,0.15); color: #c6c6cc; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#051424] dark:text-[#d4e4fa]" x-data="appShell">
    <header class="pub-header sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-indigo-600 to-blue-500 font-black text-white shadow-lg shadow-indigo-500/20">L</span>
                <span>
                    <span class="block text-lg font-black tracking-tight text-slate-900 dark:text-[#d4e4fa]">LibraFlow</span>
                    <span class="block text-xs text-slate-500 dark:text-[#c6c6cc]">Modern Library System</span>
                </span>
            </a>
            <nav class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('books.search') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:block dark:text-[#c6c6cc] dark:hover:bg-[#1c2b3c] dark:hover:text-[#d4e4fa]">Catalog</a>
                @guest('member')
                    <a href="{{ route('member.register') }}" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 sm:block dark:text-[#c6c6cc] dark:hover:bg-[#1c2b3c] dark:hover:text-[#d4e4fa]">Membership</a>
                @endguest
                <button type="button" @click="toggleTheme()" class="grid size-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 dark:border-white/10 dark:bg-[#1c2b3c] dark:text-[#d4e4fa]" aria-label="Toggle color theme">
                    <span x-text="dark ? 'Light' : 'Dark'" class="text-[10px] font-bold"></span>
                </button>
                @auth('member')
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:bg-[#1c2b3c] dark:text-[#d4e4fa] dark:hover:bg-[#25374d]">
                            <svg class="size-5 text-slate-500 dark:text-[#c6c6cc]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                            <span class="max-w-[100px] truncate hidden sm:inline">{{ auth('member')->user()->first_name }}</span>
                            <svg class="size-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        
                        <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl border border-slate-200 bg-white p-1 shadow-lg dark:border-white/10 dark:bg-[#1c2b3c] z-50">
                            <a href="{{ route('member.profile') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-[#d4e4fa] dark:hover:bg-[#25374d]">Setting Profil</a>
                            <hr class="my-1 border-slate-100 dark:border-white/5" />
                            <form method="POST" action="{{ route('member.logout') }}" class="block">
                                @csrf
                                <button type="submit" class="w-full text-left block rounded-lg px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">Keluar</button>
                            </form>
                        </div>
                    </div>
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

    <footer class="pub-footer mt-16 border-t border-slate-200 bg-white dark:bg-[#0d1c2d] dark:border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-[#c6c6cc]">
            <p>&copy; {{ date('Y') }} LibraFlow. Built for modern campus libraries.</p>
            <p>Discover. Borrow. Learn.</p>
        </div>
    </footer>
</body>
</html>
