@extends('layouts.app')

@section('title', 'Login member - LibraFlow')

@section('content')
<section class="mx-auto grid min-h-[70vh] max-w-6xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:px-8">
    <div>
        <p class="font-bold text-indigo-600 dark:text-indigo-400">Pembaca digital</p>
        <h1 class="mt-2 text-4xl font-black">Masuk dan lanjutkan membaca.</h1>
        <p class="mt-4 max-w-lg leading-7 text-slate-500 dark:text-slate-400">Gunakan username atau email yang didaftarkan. Member pending maupun approved dapat membaca buku digital; member rejected tidak dapat masuk.</p>
    </div>

    <form method="POST" action="{{ route('member.login.store') }}" class="panel space-y-5 p-6 sm:p-8" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <div>
            <label class="label" for="login">Username atau email</label>
            <input id="login" class="input" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
            <x-field-error name="login" />
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input id="password" class="input" type="password" name="password" autocomplete="current-password" required>
            <x-field-error name="password" />
        </div>
        <label class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600">
            Ingat saya di perangkat ini
        </label>
        <button class="btn-primary w-full" :disabled="submitting">
            <span x-text="submitting ? 'Memproses...' : 'Masuk sebagai member'"></span>
        </button>
        <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-slate-300 dark:border-slate-800"></div>
            <span class="flex-shrink mx-4 text-xs text-slate-400 uppercase">Atau</span>
            <div class="flex-grow border-t border-slate-300 dark:border-slate-800"></div>
        </div>
        <a href="{{ route('auth.google.redirect', 'member') }}" class="flex items-center justify-center gap-3 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-850">
            <svg class="w-5 h-5 shrink-0" width="20" height="20" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
            </svg>
            <span>Masuk dengan Google</span>
        </a>
        <p class="text-center text-sm text-slate-500">Belum punya akun? <a class="font-bold text-indigo-600" href="{{ route('member.register') }}">Daftar membership</a></p>
    </form>
</section>
@endsection
