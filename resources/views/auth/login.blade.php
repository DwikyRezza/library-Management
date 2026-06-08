@extends('layouts.app')

@section('title', 'Staff login - LibraFlow')

@section('content')
<section class="mx-auto grid min-h-[70vh] max-w-5xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8">
    <div class="hidden lg:block">
        <p class="section-kicker">Staff workspace</p>
        <h1 class="mt-2 text-5xl font-black text-slate-950 dark:text-white">Keep every shelf and transaction in flow.</h1>
        <p class="mt-5 leading-7 text-slate-500 dark:text-slate-400">Sign in to manage catalog records, member approvals, circulation, and operational reports.</p>
    </div>
    <form method="POST" action="{{ route('login.store') }}" class="panel p-7 sm:p-9" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <div class="mb-7">
            <p class="section-kicker">Welcome back</p>
            <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Staff login</h2>
        </div>
        <div>
            <label class="label">Email or username</label>
            <input class="input" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
            <x-field-error name="login" />
        </div>
        <div class="mt-5">
            <label class="label">Password</label>
            <input class="input" type="password" name="password" autocomplete="current-password" required>
            <x-field-error name="password" />
        </div>
        <label class="mt-5 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-blue-600">
            Remember me
        </label>
        <button class="btn-primary mt-7 w-full" :disabled="submitting">
            <span x-text="submitting ? 'Signing in...' : 'Sign in'"></span>
        </button>
        <div class="relative flex items-center py-3">
            <div class="flex-grow border-t border-slate-200 dark:border-white/10"></div>
            <span class="mx-4 flex-shrink text-xs uppercase text-slate-400">Or</span>
            <div class="flex-grow border-t border-slate-200 dark:border-white/10"></div>
        </div>
        <a href="{{ route('auth.google.redirect', 'staff') }}" class="btn-secondary w-full gap-3">
            <svg class="size-5 shrink-0" width="20" height="20" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
            </svg>
            <span>Sign in with Google</span>
        </a>
        <p class="mt-5 text-center text-xs text-slate-400">Authorized library staff only.</p>
    </form>
</section>
@endsection
