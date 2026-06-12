@extends('layouts.app')

@section('title', 'Lengkapi Profil - Lyrary')

@section('content')
<section class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
    <div>
        <p class="section-kicker">Profil Keanggotaan</p>
        <h1 class="mt-2 text-4xl font-black text-slate-950 dark:text-white">Lengkapi data diri Anda.</h1>
        <p class="mt-4 leading-7 text-slate-500 dark:text-slate-400">Lengkapi data profil keanggotaan agar layanan perpustakaan bisa digunakan secara penuh.</p>

        <div class="panel mt-8 p-5">
            <h2 class="font-bold">Informasi alur approval</h2>
            <ol class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <li><strong>1.</strong> Lengkapi data diri asli Anda.</li>
                <li><strong>2.</strong> Kirim pembaruan profil.</li>
                <li><strong>3.</strong> Pustakawan akan memverifikasi data untuk menyetujui akun.</li>
                <li><strong>4.</strong> Setelah disetujui, Anda dapat meminjam buku fisik di perpustakaan.</li>
            </ol>
        </div>
    </div>

    <div>
        @if (auth('member')->user()->isProfileIncomplete())
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-200">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-2">
                    <span class="font-bold">Profil belum lengkap:</span>
                    <span>Lengkapi data diri terlebih dahulu sebelum mengakses buku digital dan layanan lain.</span>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('member.profile.update') }}" class="panel grid gap-5 p-6 sm:grid-cols-2" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            @method('PUT')

            <div class="sm:col-span-2">
                <label class="label">Alamat Email (Akun Google)</label>
                <input class="input cursor-not-allowed bg-slate-100 text-slate-500 dark:bg-slate-950/60 dark:text-slate-400" type="email" name="email" value="{{ $member->email }}" readonly>
                <p class="mt-1.5 text-xs text-slate-400">Email di atas ditautkan dengan Google Sign-In dan tidak dapat diubah.</p>
            </div>

            <div><label class="label">Nama Depan (First Name)</label><input class="input" name="first_name" value="{{ old('first_name', $member->first_name) }}" required><x-field-error name="first_name" /></div>
            <div><label class="label">Nama Belakang (Last Name)</label><input class="input" name="last_name" value="{{ old('last_name', $member->last_name) }}" required><x-field-error name="last_name" /></div>
            <div><label class="label">Nama Pengguna (Username)</label><input class="input" name="username" value="{{ old('username', $member->username) }}" required autocomplete="username"><x-field-error name="username" /></div>
            <div><label class="label">Nomor Telepon (Phone)</label><input class="input" name="phone" value="{{ old('phone', $member->phone) }}" placeholder="Contoh: 08123456789" required><x-field-error name="phone" /></div>

            <div>
                <label class="label">Nomor Induk Mahasiswa (NIM / Roll Number)</label>
                <input class="input font-mono" name="roll_number" value="{{ old('roll_number', str_starts_with($member->roll_number, 'GGL-') ? '' : $member->roll_number) }}" placeholder="Masukkan NIM asli Anda" required>
                <p class="mt-1 text-xs text-slate-400">Harap ganti kode unik Google (GGL-...) dengan NIM resmi dari kampus.</p>
                <x-field-error name="roll_number" />
            </div>

            <div>
                <label class="label">Tahun Angkatan / Semester (Tahun)</label>
                <select class="input" name="year" required>
                    <option value="">Pilih Tahun Angkatan</option>
                    @for ($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" @selected(old('year', $member->year) == $i)>Tahun Ke-{{ $i }} (Semester {{ ($i * 2) - 1 }} / {{ $i * 2 }})</option>
                    @endfor
                </select>
                <x-field-error name="year" />
            </div>

            <div>
                <label class="label">Program Studi / Jurusan (Branch)</label>
                <select class="input" name="branch_id" required>
                    <option value="">Pilih Program Studi</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $member->branch_id) == $branch->id)>{{ $branch->name }} ({{ $branch->code }})</option>
                    @endforeach
                </select>
                <x-field-error name="branch_id" />
            </div>

            <div>
                <label class="label">Kategori Anggota (Member Category)</label>
                <select class="input" name="member_category_id" required>
                    <option value="">Pilih Kategori Keanggotaan</option>
                    @foreach ($memberCategories as $category)
                        <option value="{{ $category->id }}" @selected(old('member_category_id', $member->member_category_id) == $category->id)>{{ $category->name }} (Maks. {{ $category->max_books }} buku)</option>
                    @endforeach
                </select>
                <x-field-error name="member_category_id" />
            </div>

            <div class="mt-4 sm:col-span-2">
                <button class="btn-primary w-full" :disabled="submitting">
                    <span x-text="submitting ? 'Menyimpan...' : 'Simpan perubahan dan selesaikan profil'"></span>
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
