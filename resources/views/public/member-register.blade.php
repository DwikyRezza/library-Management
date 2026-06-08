@extends('layouts.app')

@section('title', 'Member registration - LibraFlow')

@section('content')
<section class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
    <div>
        <p class="font-bold text-indigo-600 dark:text-indigo-400">Membership</p>
        <h1 class="mt-2 text-4xl font-black">Open the door to more learning.</h1>
        <p class="mt-4 leading-7 text-slate-500 dark:text-slate-400">Daftar sekali untuk membaca koleksi digital. Persetujuan pustakawan hanya diperlukan untuk peminjaman buku fisik.</p>
        <div class="panel mt-8 p-5">
            <h2 class="font-bold">What happens next?</h2>
            <ol class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <li><strong>1.</strong> We receive your registration.</li>
                <li><strong>2.</strong> Anda dapat langsung login dan membaca buku digital.</li>
                <li><strong>3.</strong> Pustakawan dapat menyetujui akun untuk peminjaman fisik.</li>
            </ol>
        </div>
    </div>
    <form method="POST" action="{{ route('member.register.store') }}" class="panel grid gap-5 p-6 sm:grid-cols-2" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <div><label class="label">First name</label><input class="input" name="first_name" value="{{ old('first_name') }}" required><x-field-error name="first_name" /></div>
        <div><label class="label">Last name</label><input class="input" name="last_name" value="{{ old('last_name') }}" required><x-field-error name="last_name" /></div>
        <div><label class="label">Username</label><input class="input" name="username" value="{{ old('username') }}" autocomplete="username" required><x-field-error name="username" /></div>
        <div><label class="label">Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required><x-field-error name="email" /></div>
        <div><label class="label">Password</label><input class="input" type="password" name="password" autocomplete="new-password" required><x-field-error name="password" /></div>
        <div><label class="label">Konfirmasi password</label><input class="input" type="password" name="password_confirmation" autocomplete="new-password" required></div>
        <div><label class="label">Phone</label><input class="input" name="phone" value="{{ old('phone') }}"><x-field-error name="phone" /></div>
        <div><label class="label">Roll number</label><input class="input" name="roll_number" value="{{ old('roll_number') }}" required><x-field-error name="roll_number" /></div>
        <div><label class="label">Year</label><input class="input" type="number" min="1" max="8" name="year" value="{{ old('year') }}"><x-field-error name="year" /></div>
        <div><label class="label">Branch</label><select class="input" name="branch_id"><option value="">Select branch</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach</select><x-field-error name="branch_id" /></div>
        <div><label class="label">Member category</label><select class="input" name="member_category_id" required><option value="">Select category</option>@foreach ($memberCategories as $category)<option value="{{ $category->id }}" @selected(old('member_category_id') == $category->id)>{{ $category->name }} ({{ $category->max_books }} books)</option>@endforeach</select><x-field-error name="member_category_id" /></div>
        <div class="sm:col-span-2">
            <button class="btn-primary w-full" :disabled="submitting">
                <span x-text="submitting ? 'Submitting...' : 'Submit registration'"></span>
            </button>
            <div class="relative flex py-3 items-center">
                <div class="flex-grow border-t border-slate-350 dark:border-slate-800"></div>
                <span class="flex-shrink mx-4 text-xs text-slate-400 uppercase">Atau</span>
                <div class="flex-grow border-t border-slate-350 dark:border-slate-800"></div>
            </div>
            <a href="{{ route('auth.google.redirect', 'member') }}" class="flex items-center justify-center gap-3 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-850">
                <svg class="w-5 h-5 shrink-0" width="20" height="20" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                </svg>
                <span>Daftar dengan Google</span>
            </a>
        </div>
    </form>
</section>
@endsection
