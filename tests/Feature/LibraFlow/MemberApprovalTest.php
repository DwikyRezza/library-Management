<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_approve_pending_member_idempotently(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_LIBRARIAN]);
        $member = Member::factory()->pending()->create();

        $this->actingAs($user)->post("/admin/members/{$member->id}/approve")->assertRedirect();
        $this->actingAs($user)->post("/admin/members/{$member->id}/approve")->assertRedirect();

        $member->refresh();
        $this->assertTrue($member->approved);
        $this->assertFalse($member->rejected);
        $this->assertNotNull($member->approved_at);
    }

    public function test_librarian_can_reject_pending_member_idempotently(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_LIBRARIAN]);
        $member = Member::factory()->pending()->create();

        $this->actingAs($user)->post("/admin/members/{$member->id}/reject")->assertRedirect();
        $this->actingAs($user)->post("/admin/members/{$member->id}/reject")->assertRedirect();

        $member->refresh();
        $this->assertFalse($member->approved);
        $this->assertTrue($member->rejected);
        $this->assertNotNull($member->rejected_at);
    }
}
