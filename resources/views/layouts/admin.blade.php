<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-libraflow.png') }}">
    <title>@yield('title', 'Admin') - Lyrary</title>
    <script>
        (() => {
            const saved = localStorage.getItem('lyrary-theme');
            const dark = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html.dark .admin-sidebar { background-color: rgba(15, 23, 36, 0.96); border-color: rgba(255,255,255,0.10); }
        html.dark .admin-header { background-color: rgba(16, 25, 39, 0.88); border-color: rgba(255,255,255,0.10); }
    </style>
</head>
<body class="min-h-screen text-slate-900 dark:text-slate-100" x-data="appShell">
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"></div>

    <aside class="admin-sidebar fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200/80 bg-white/90 text-slate-800 shadow-xl shadow-slate-200/40 backdrop-blur transition-transform duration-200 dark:text-slate-100 dark:shadow-none lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-20 items-center justify-between border-b border-slate-200/80 px-6 dark:border-white/10">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo-libraflow.png') }}" alt="Lyrary" class="size-10 rounded-lg object-contain">
                <span>
                    <strong class="block text-lg">Lyrary</strong>
                    <small class="text-slate-500 dark:text-slate-400">Staff workspace</small>
                </span>
            </a>
            <button @click="sidebarOpen = false" class="text-2xl lg:hidden" aria-label="Close navigation">&times;</button>
        </div>

        @php
            $links = [
                ['admin.dashboard', 'Dashboard', 'Overview'],
                ['admin.books.index', 'Books', 'Catalog'],
                ['admin.categories.index', 'Categories', 'Taxonomy'],
                ['admin.members.index', 'Members', 'Community'],
                ['admin.members.pending', 'Approvals', 'Pending'],
                ['admin.circulation.index', 'Circulation', 'Issue & return'],
                ['admin.transactions.index', 'Transactions', 'History'],
                ['admin.reports.index', 'Reports', 'Insights'],
            ];
            if (auth()->user()->isAdmin()) {
                $links[] = ['admin.reading-history.index', 'Reading history', 'Admin only'];
            }
        @endphp
        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">
            @foreach ($links as [$routeName, $label, $hint])
                @php
                    $pattern = str_contains($routeName, '.index')
                        ? str_replace('.index', '.*', $routeName)
                        : $routeName;
                    $isActive = request()->routeIs($pattern)
                        && ! ($routeName === 'admin.members.index' && request()->routeIs('admin.members.pending'));
                @endphp
                <a href="{{ route($routeName) }}"
                   class="flex items-center justify-between rounded-lg px-4 py-3 transition {{ $isActive ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/10' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white' }}">
                    <span class="font-semibold">{{ $label }}</span>
                    <span class="text-xs opacity-60">{{ $hint }}</span>
                </a>
            @endforeach
        </nav>
        <div class="border-t border-slate-200/80 p-4 dark:border-white/10">
            <a href="{{ route('home') }}" class="block rounded-lg px-4 py-3 text-sm font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white">View public catalog</a>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="admin-header sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur sm:px-6">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="grid size-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-400/30 dark:hover:bg-blue-500/10 dark:hover:text-blue-200 lg:hidden"
                    aria-label="Buka navigasi"
                    title="Buka navigasi"
                >
                    <i data-lucide="menu" class="size-5" aria-hidden="true"></i>
                </button>
                <div>
                    <h1 class="font-bold text-slate-950 dark:text-slate-100">@yield('page-title', 'Dashboard')</h1>
                    <p class="hidden text-xs text-slate-500 sm:block dark:text-slate-400">@yield('page-description', 'Library operations at a glance')</p>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    @click="toggleTheme()"
                    class="grid size-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-400/30 dark:hover:bg-blue-500/10 dark:hover:text-blue-200"
                    :aria-label="dark ? 'Gunakan tema terang' : 'Gunakan tema gelap'"
                    :title="dark ? 'Gunakan tema terang' : 'Gunakan tema gelap'"
                >
                    <i x-show="!dark" data-lucide="moon" class="size-5" aria-hidden="true"></i>
                    <i x-show="dark" data-lucide="sun" class="size-5" aria-hidden="true"></i>
                </button>
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                    <p class="text-xs capitalize text-slate-500 dark:text-slate-400">{{ auth()->user()->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="grid size-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-white/10 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-rose-400/30 dark:hover:bg-rose-500/10 dark:hover:text-rose-200"
                        aria-label="Keluar"
                        title="Keluar"
                    >
                        <i data-lucide="log-out" class="size-5" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8">
            <x-alert />
            @yield('content')
        </main>
    </div>
</body>
</html>
