<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect to the Google authentication page.
     */
    public function redirectToGoogle(string $type): RedirectResponse
    {
        if (! in_array($type, ['member', 'staff'], true)) {
            abort(404);
        }

        session(['socialite_type' => $type]);

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        $type = session('socialite_type', 'member');

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            $targetRoute = $type === 'staff' ? 'login' : 'member.login';
            return redirect()->route($targetRoute)->with('error', 'Gagal melakukan otentikasi dengan Google.');
        }

        if ($type === 'staff') {
            return $this->handleStaffLogin($googleUser);
        }

        return $this->handleMemberLoginOrRegister($googleUser);
    }

    /**
     * Handle Google login for staff members.
     */
    private function handleStaffLogin($googleUser): RedirectResponse
    {
        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $user) {
            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Email Google Anda tidak terdaftar sebagai staf.']);
        }

        if (! $user->is_active) {
            return redirect()
                ->route('login')
                ->withErrors(['login' => 'Akun staf Anda tidak aktif. Silakan hubungi administrator.']);
        }

        // Update google_id if not set
        if (blank($user->google_id)) {
            $user->forceFill(['google_id' => $googleUser->getId()])->save();
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', "Selamat datang kembali, {$user->name}!");
    }

    /**
     * Handle Google login or registration for members.
     */
    private function handleMemberLoginOrRegister($googleUser): RedirectResponse
    {
        $member = Member::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $member) {
            // Register as new member
            $fullName = $googleUser->getName() ?? 'Google User';
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $googleUser->getRaw()['given_name'] ?? $nameParts[0] ?? 'Google';
            $lastName = $googleUser->getRaw()['family_name'] ?? $nameParts[1] ?? 'User';

            // Generate unique username
            $baseUsername = Str::slug($firstName . $lastName, '');
            if (blank($baseUsername)) {
                $baseUsername = explode('@', $googleUser->getEmail())[0];
            }
            $baseUsername = preg_replace('/[^A-Za-z0-9._-]/', '', $baseUsername);
            $username = $baseUsername;
            $counter = 1;
            while (Member::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            // Generate unique roll number
            do {
                $rollNumber = 'GGL-' . Str::upper(Str::random(10));
            } while (Member::where('roll_number', $rollNumber)->exists());

            // Generate unique member code
            do {
                $memberCode = 'MBR-' . Str::upper(Str::substr((string) Str::ulid(), -8));
            } while (Member::withTrashed()->where('member_code', $memberCode)->exists());

            // Default category
            $category = MemberCategory::query()->where('name', 'Regular Student')->first()
                ?? MemberCategory::query()->first();

            $member = Member::query()->create([
                'member_code' => $memberCode,
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(24)),
                'roll_number' => $rollNumber,
                'member_category_id' => $category->id,
                'google_id' => $googleUser->getId(),
                'approved' => false,
                'rejected' => false,
                'books_borrowed_count' => 0,
            ]);

            Auth::guard('member')->login($member);
            request()->session()->regenerate();

            return redirect()
                ->route('books.search')
                ->with('success', 'Registrasi berhasil dan Anda telah masuk. Silakan lengkapi profil Anda nanti jika diperlukan.');
        }

        if ($member->rejected) {
            return redirect()
                ->route('member.login')
                ->withErrors(['login' => 'Keanggotaan Anda telah dinonaktifkan. Silakan hubungi perpustakaan.']);
        }

        // Update google_id if matched by email first
        if (blank($member->google_id)) {
            $member->forceFill(['google_id' => $googleUser->getId()])->save();
        }

        Auth::guard('member')->login($member);
        request()->session()->regenerate();

        return redirect()->route('books.search')->with('success', "Selamat datang kembali, {$member->first_name}!");
    }
}
