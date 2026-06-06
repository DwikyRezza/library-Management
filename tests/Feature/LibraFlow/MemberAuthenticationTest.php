<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_member_credentials_with_a_hashed_password(): void
    {
        $branch = Branch::factory()->create();
        $memberCategory = MemberCategory::factory()->create();

        $this->post('/member/register', [
            'first_name' => 'Rani',
            'last_name' => 'Permata',
            'username' => 'rani.permata',
            'email' => 'rani@example.test',
            'password' => 'very-secret-password',
            'password_confirmation' => 'very-secret-password',
            'phone' => '08123456789',
            'roll_number' => 'STU-2026-999',
            'branch_id' => $branch->id,
            'year' => 2,
            'member_category_id' => $memberCategory->id,
        ])->assertRedirect('/member/login');

        $member = Member::query()->where('username', 'rani.permata')->firstOrFail();

        $this->assertFalse($member->approved);
        $this->assertFalse($member->rejected);
        $this->assertTrue(Hash::check('very-secret-password', $member->password));
        $this->assertNotSame('very-secret-password', $member->password);
    }

    public function test_pending_member_can_login_with_username(): void
    {
        $member = Member::factory()->pending()->create([
            'username' => 'pending.reader',
            'password' => 'password',
        ]);

        $this->post('/member/login', [
            'login' => 'pending.reader',
            'password' => 'password',
        ])->assertRedirect('/books/search');

        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_approved_member_can_login_with_email(): void
    {
        $member = Member::factory()->create([
            'email' => 'reader@example.test',
            'password' => 'password',
        ]);

        $this->post('/member/login', [
            'login' => 'reader@example.test',
            'password' => 'password',
        ])->assertRedirect('/books/search');

        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_rejected_member_cannot_login(): void
    {
        Member::factory()->rejected()->create([
            'username' => 'rejected.reader',
            'password' => 'password',
        ]);

        $this->post('/member/login', [
            'login' => 'rejected.reader',
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest('member');
    }
}
