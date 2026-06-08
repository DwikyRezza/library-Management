<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - LibraFlow</title>
    <script>
        (() => {
            const saved = localStorage.getItem('libraflow-theme');
            const dark = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-background dark:text-on-background" x-data="appShell">
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-800 bg-slate-950 text-slate-100 transition-transform duration-200 lg:translate-x-0 dark:bg-surface-container-low dark:border-outline-variant/10"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-20 items-center justify-between border-b border-slate-800 dark:border-outline-variant/10 px-6">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 font-black text-white">L</span>
                <span><strong class="block text-lg">LibraFlow</strong><small class="text-slate-400">Staff workspace</small></span>
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
                   class="flex items-center justify-between rounded-xl px-4 py-3 transition {{ $isActive ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-300 hover:bg-slate-900 hover:text-white dark:text-on-surface-variant dark:hover:bg-surface-container-high dark:hover:text-on-surface' }}">
                    <span class="font-semibold">{{ $label }}</span>
                    <span class="text-xs opacity-70">{{ $hint }}</span>
                </a>
            @endforeach
        </nav>
        <div class="border-t border-slate-800 dark:border-outline-variant/10 p-4">
            <a href="{{ route('home') }}" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-300 hover:bg-slate-900 hover:text-white dark:text-on-surface-variant dark:hover:bg-surface-container-high dark:hover:text-on-surface">View public catalog</a>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 dark:border-outline-variant/10 dark:bg-surface-container/90">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="grid size-10 place-items-center rounded-xl border border-slate-200 lg:hidden dark:border-outline-variant/30" aria-label="Open navigation">Menu</button>
                <div>
                    <h1 class="font-bold text-slate-900 dark:text-on-surface">@yield('page-title', 'Dashboard')</h1>
                    <p class="hidden text-xs text-slate-500 sm:block dark:text-on-surface-variant">@yield('page-description', 'Library operations at a glance')</p>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <button type="button" @click="toggleTheme()" class="grid size-10 place-items-center rounded-xl border border-slate-200 dark:border-outline-variant/30" aria-label="Toggle color theme">
                    <span x-text="dark ? 'Light' : 'Dark'" class="text-[10px] font-bold"></span>
                </button>
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold text-slate-900 dark:text-on-surface">{{ auth()->user()->name }}</p>
                    <p class="text-xs capitalize text-slate-500 dark:text-on-surface-variant">{{ auth()->user()->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secondary !px-3">Logout</button>
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
