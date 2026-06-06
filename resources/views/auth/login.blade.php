@extends('layouts.app')

@section('title', 'Staff login - LibraFlow')

@section('content')
<section class="mx-auto grid min-h-[70vh] max-w-5xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8">
    <div class="hidden lg:block">
        <p class="font-bold text-indigo-600 dark:text-indigo-400">Staff workspace</p>
        <h1 class="mt-2 text-5xl font-black">Keep every shelf and transaction in flow.</h1>
        <p class="mt-5 leading-7 text-slate-500 dark:text-slate-400">Sign in to manage catalog records, member approvals, circulation, and operational reports.</p>
    </div>
    <form method="POST" action="{{ route('login.store') }}" class="panel p-7 sm:p-9" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <div class="mb-7"><p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">Welcome back</p><h2 class="mt-1 text-2xl font-black">Staff login</h2></div>
        <div><label class="label">Email or username</label><input class="input" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus><x-field-error name="login" /></div>
        <div class="mt-5"><label class="label">Password</label><input class="input" type="password" name="password" autocomplete="current-password" required><x-field-error name="password" /></div>
        <label class="mt-5 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600"> Remember me</label>
        <button class="btn-primary mt-7 w-full" :disabled="submitting"><span x-text="submitting ? 'Signing in...' : 'Sign in'"></span></button>
        <p class="mt-5 text-center text-xs text-slate-400">Authorized library staff only.</p>
    </form>
</section>
@endsection
