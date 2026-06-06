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
        <p class="text-center text-sm text-slate-500">Belum punya akun? <a class="font-bold text-indigo-600" href="{{ route('member.register') }}">Daftar membership</a></p>
    </form>
</section>
@endsection
