<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use App\Models\Branch;
use App\Models\MemberCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_email_and_reach_dashboard(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@libraflow.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'admin@libraflow.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_admin_can_login_with_username(): void
    {
        User::factory()->create([
            'username' => 'head.librarian',
            'email' => 'head@libraflow.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'login' => 'head.librarian',
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticated();
    }

    public function test_inactive_staff_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'inactive',
            'email' => 'inactive@libraflow.test',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'login' => 'inactive',
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_public_book_search_finds_books_by_title(): void
    {
        $category = BookCategory::factory()->create(['name' => 'Technology']);
        $book = Book::factory()->create([
            'title' => 'Laravel Systems',
            'author' => 'Ayu Kurnia',
            'category_id' => $category->id,
            'total_copies' => 1,
            'available_copies' => 1,
        ]);
        BookCopy::factory()->create(['book_id' => $book->id, 'copy_code' => 'LIB-0001-001']);

        $response = $this->get('/books/search?q=Laravel');

        $response->assertOk();
        $response->assertSee('Laravel Systems');
        $response->assertSee('Available');
    }

    public function test_public_member_registration_creates_pending_member(): void
    {
        $branch = Branch::factory()->create();
        $memberCategory = MemberCategory::factory()->create(['name' => 'Regular Student']);

        $response = $this->post('/member/register', [
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
        ]);

        $response->assertRedirect('/member/login');
        $this->assertDatabaseHas('members', [
            'email' => 'rani@example.test',
            'approved' => false,
            'rejected' => false,
        ]);
    }
}
