<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile_page(): void
    {
        $response = $this->get('/member/profile');
        $response->assertRedirect(route('member.login'));
    }

    public function test_member_can_access_profile_page(): void
    {
        $category = MemberCategory::factory()->create();
        $member = Member::factory()->create(['member_category_id' => $category->id]);

        $response = $this->actingAs($member, 'member')->get('/member/profile');
        $response->assertOk();
        $response->assertViewIs('member.profile');
    }

    public function test_member_with_incomplete_profile_can_update_to_complete(): void
    {
        $category = MemberCategory::factory()->create();
        $branch = Branch::factory()->create();

        // Create incomplete member (blank phone, branch, year, roll_number starting with GGL-)
        $member = Member::factory()->create([
            'phone' => null,
            'branch_id' => null,
            'year' => null,
            'roll_number' => 'GGL-98765432',
            'member_category_id' => $category->id,
            'approved' => false,
            'rejected' => false,
        ]);

        $this->assertTrue($member->isProfileIncomplete());

        $response = $this->actingAs($member, 'member')
            ->put('/member/profile', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'username' => 'johndoe',
                'phone' => '08123456789',
                'roll_number' => 'STU-12345',
                'branch_id' => $branch->id,
                'year' => 2,
                'member_category_id' => $category->id,
            ]);

        $response->assertRedirect(route('books.search'));
        $response->assertSessionHas('success');

        $freshMember = $member->fresh();
        $this->assertFalse($freshMember->isProfileIncomplete());
        $this->assertSame('08123456789', $freshMember->phone);
        $this->assertSame('STU-12345', $freshMember->roll_number);
        $this->assertSame($branch->id, $freshMember->branch_id);
        $this->assertSame(2, $freshMember->year);
        $this->assertFalse($freshMember->approved); // approval stays pending!
    }

    public function test_validation_rejects_duplicate_username_and_roll_number(): void
    {
        $category = MemberCategory::factory()->create();
        $branch = Branch::factory()->create();

        $existingMember = Member::factory()->create([
            'username' => 'existinguser',
            'roll_number' => 'STU-99999',
            'member_category_id' => $category->id,
        ]);

        $member = Member::factory()->create([
            'username' => 'myuser',
            'roll_number' => 'STU-11111',
            'member_category_id' => $category->id,
        ]);

        // Test duplicate username
        $response = $this->actingAs($member, 'member')
            ->from('/member/profile')
            ->put('/member/profile', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'username' => 'existinguser', // Duplicate
                'phone' => '08123456789',
                'roll_number' => 'STU-11111',
                'branch_id' => $branch->id,
                'year' => 2,
                'member_category_id' => $category->id,
            ]);

        $response->assertRedirect('/member/profile');
        $response->assertSessionHasErrors('username');

        // Test duplicate roll number
        $response = $this->actingAs($member, 'member')
            ->from('/member/profile')
            ->put('/member/profile', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'username' => 'myuser',
                'phone' => '08123456789',
                'roll_number' => 'STU-99999', // Duplicate
                'branch_id' => $branch->id,
                'year' => 2,
                'member_category_id' => $category->id,
            ]);

        $response->assertRedirect('/member/profile');
        $response->assertSessionHasErrors('roll_number');
    }

    public function test_validation_allows_same_username_and_roll_number_for_owner(): void
    {
        $category = MemberCategory::factory()->create();
        $branch = Branch::factory()->create();

        $member = Member::factory()->create([
            'username' => 'myuser',
            'roll_number' => 'STU-11111',
            'member_category_id' => $category->id,
            'phone' => '08111',
            'branch_id' => $branch->id,
            'year' => 1,
        ]);

        // Submit same username and roll number, just changing phone
        $response = $this->actingAs($member, 'member')
            ->put('/member/profile', [
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'username' => 'myuser', // Same
                'phone' => '08999999999', // Changed
                'roll_number' => 'STU-11111', // Same
                'branch_id' => $branch->id,
                'year' => 1,
                'member_category_id' => $category->id,
            ]);

        $response->assertRedirect(route('books.search'));
        $this->assertSame('08999999999', $member->fresh()->phone);
    }

    public function test_incomplete_member_redirected_to_profile_page_by_middleware(): void
    {
        $category = MemberCategory::factory()->create();
        $bookCategory = BookCategory::factory()->create();
        $book = Book::factory()->create(['category_id' => $bookCategory->id]);

        // Create incomplete member
        $member = Member::factory()->create([
            'phone' => null,
            'member_category_id' => $category->id,
        ]);

        $this->assertTrue($member->isProfileIncomplete());

        // Try to access digital reader open path
        $response = $this->actingAs($member, 'member')
            ->get(route('member.reader.open', $book));

        $response->assertRedirect(route('member.profile'));
        $response->assertSessionHas('warning', 'Lengkapi profil Anda terlebih dahulu sebelum melanjutkan.');
    }
}
