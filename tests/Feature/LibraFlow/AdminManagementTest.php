<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_librarian_can_create_book_with_generated_copies(): void
    {
        $user = User::factory()->create();
        $category = BookCategory::factory()->create();

        $this->actingAs($user)->post('/admin/books', [
            'title' => 'Reliable Laravel',
            'author' => 'Rani Putri',
            'publisher' => 'Campus Press',
            'publication_year' => 2026,
            'isbn' => '9780000000001',
            'category_id' => $category->id,
            'description' => 'Production patterns.',
            'number_of_copies' => 2,
            'shelf_location' => 'A-01',
        ])->assertRedirect();

        $book = Book::query()->where('title', 'Reliable Laravel')->firstOrFail();
        $this->assertSame(2, $book->total_copies);
        $this->assertSame(2, $book->available_copies);
        $this->assertDatabaseHas('book_copies', ['copy_code' => sprintf('LIB-%04d-001', $book->id)]);
        $this->assertDatabaseHas('book_copies', ['copy_code' => sprintf('LIB-%04d-002', $book->id)]);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = BookCategory::factory()->create();
        Book::factory()->for($category, 'category')->create();

        $this->actingAs($user)
            ->delete("/admin/categories/{$category->id}")
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('book_categories', ['id' => $category->id]);
    }

    public function test_category_referenced_by_archived_book_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = BookCategory::factory()->create();
        Book::factory()->for($category, 'category')->create()->delete();

        $this->actingAs($user)
            ->delete("/admin/categories/{$category->id}")
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('book_categories', ['id' => $category->id]);
    }

    public function test_borrowed_copy_cannot_be_marked_lost(): void
    {
        $user = User::factory()->create();
        $copy = BookCopy::factory()->create(['status' => BookCopy::STATUS_BORROWED]);
        $member = Member::factory()->create();
        BorrowTransaction::factory()->create([
            'book_copy_id' => $copy->id,
            'member_id' => $member->id,
            'issued_by' => $user->id,
            'status' => BorrowTransaction::STATUS_BORROWED,
            'returned_at' => null,
        ]);

        $this->actingAs($user)
            ->patch("/admin/book-copies/{$copy->id}", ['status' => BookCopy::STATUS_LOST])
            ->assertSessionHasErrors('status');

        $this->assertSame(BookCopy::STATUS_BORROWED, $copy->fresh()->status);
    }

    public function test_member_with_active_loan_cannot_be_archived(): void
    {
        $user = User::factory()->create();
        $copy = BookCopy::factory()->create(['status' => BookCopy::STATUS_BORROWED]);
        $member = Member::factory()->create(['books_borrowed_count' => 1]);
        BorrowTransaction::factory()->create([
            'book_copy_id' => $copy->id,
            'member_id' => $member->id,
            'issued_by' => $user->id,
            'status' => BorrowTransaction::STATUS_BORROWED,
            'returned_at' => null,
        ]);

        $this->actingAs($user)
            ->delete("/admin/members/{$member->id}")
            ->assertSessionHasErrors('member');

        $this->assertNotSoftDeleted($member);
    }

    public function test_core_admin_pages_render_with_related_data(): void
    {
        $user = User::factory()->create();
        $category = BookCategory::factory()->create();
        $book = Book::factory()->for($category, 'category')->create();
        $copy = BookCopy::factory()->for($book)->create();
        $member = Member::factory()->create();
        $transaction = BorrowTransaction::factory()->create([
            'book_copy_id' => $copy->id,
            'member_id' => $member->id,
            'issued_by' => $user->id,
        ]);

        $urls = [
            '/admin/dashboard',
            '/admin/categories',
            '/admin/categories/create',
            "/admin/categories/{$category->id}/edit",
            '/admin/books',
            '/admin/books/create',
            "/admin/books/{$book->id}",
            "/admin/books/{$book->id}/edit",
            '/admin/members',
            '/admin/members/pending',
            "/admin/members/{$member->id}",
            "/admin/members/{$member->id}/edit",
            '/admin/circulation',
            '/admin/transactions',
            "/admin/transactions/{$transaction->id}",
            '/admin/reports',
        ];

        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_book_detail_exposes_edit_and_delete_actions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.books.show', $book))
            ->assertOk()
            ->assertSee(route('admin.books.edit', $book), false)
            ->assertSee(route('admin.books.destroy', $book), false)
            ->assertSee('Hapus buku');
    }

    public function test_member_index_exposes_delete_action_for_admins(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = Member::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee(route('admin.members.destroy', $member), false)
            ->assertSee('Hapus member');
    }

    public function test_admin_can_delete_a_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->delete("/admin/books/{$book->id}")
            ->assertRedirect(route('admin.books.index'))
            ->assertSessionHas('success', 'Buku berhasil dihapus.');

        $this->assertSoftDeleted($book);
    }

    public function test_admin_can_delete_all_books(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        $this->actingAs($user)
            ->delete('/admin/books/delete-all')
            ->assertRedirect(route('admin.books.index'))
            ->assertSessionHas('success', 'Semua buku berhasil dihapus.');

        $this->assertSoftDeleted($book1);
        $this->assertSoftDeleted($book2);
    }

    public function test_delete_all_skips_books_with_active_loans(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        $copy = BookCopy::factory()->create([
            'book_id' => $book2->id,
            'status' => BookCopy::STATUS_BORROWED,
        ]);
        $member = Member::factory()->create();
        BorrowTransaction::factory()->create([
            'book_copy_id' => $copy->id,
            'member_id' => $member->id,
            'issued_by' => $user->id,
            'status' => BorrowTransaction::STATUS_BORROWED,
            'returned_at' => null,
        ]);

        $this->actingAs($user)
            ->delete('/admin/books/delete-all')
            ->assertRedirect(route('admin.books.index'))
            ->assertSessionHas('success', 'Berhasil menghapus 1 buku. 1 buku gagal dihapus karena sedang dipinjam.');

        $this->assertSoftDeleted($book1);
        $this->assertNotSoftDeleted($book2);
    }
}
