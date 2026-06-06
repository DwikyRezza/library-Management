<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\Member;
use App\Models\MemberCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CirculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_book_success_updates_transaction_copy_member_and_book(): void
    {
        [$user, $member, $book, $copy] = $this->circulationFixtures();

        $response = $this->actingAs($user)->post('/admin/circulation/issue', [
            'book_copy_code' => $copy->copy_code,
            'member_lookup' => $member->member_code,
            'notes' => 'Course reference',
        ]);

        $response->assertRedirect('/admin/circulation');
        $this->assertDatabaseHas('borrow_transactions', [
            'book_copy_id' => $copy->id,
            'member_id' => $member->id,
            'status' => BorrowTransaction::STATUS_BORROWED,
        ]);
        $this->assertSame(BookCopy::STATUS_BORROWED, $copy->fresh()->status);
        $this->assertSame(1, $member->fresh()->books_borrowed_count);
        $this->assertSame(0, $book->fresh()->available_copies);
    }

    public function test_issue_book_fails_for_pending_member(): void
    {
        [$user, $member, $book, $copy] = $this->circulationFixtures(memberState: 'pending');

        $this->actingAs($user)->post('/admin/circulation/issue', [
            'book_copy_code' => $copy->copy_code,
            'member_lookup' => $member->member_code,
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('borrow_transactions', 0);
        $this->assertSame(BookCopy::STATUS_AVAILABLE, $copy->fresh()->status);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_issue_book_fails_when_member_reaches_limit(): void
    {
        [$user, $member, $book, $copy] = $this->circulationFixtures(maxBooks: 1, borrowedCount: 1);

        $this->actingAs($user)->post('/admin/circulation/issue', [
            'book_copy_code' => $copy->copy_code,
            'member_lookup' => $member->member_code,
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('borrow_transactions', 0);
        $this->assertSame(BookCopy::STATUS_AVAILABLE, $copy->fresh()->status);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_issue_book_fails_for_unavailable_copy(): void
    {
        [$user, $member, $book, $copy] = $this->circulationFixtures(copyStatus: BookCopy::STATUS_MAINTENANCE);

        $this->actingAs($user)->post('/admin/circulation/issue', [
            'book_copy_code' => $copy->copy_code,
            'member_lookup' => $member->member_code,
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('borrow_transactions', 0);
        $this->assertSame(BookCopy::STATUS_MAINTENANCE, $copy->fresh()->status);
        $this->assertSame(0, $book->fresh()->available_copies);
    }

    public function test_return_book_success_updates_transaction_copy_member_and_book(): void
    {
        [$user, $member, $book, $copy] = $this->circulationFixtures();

        $this->actingAs($user)->post('/admin/circulation/issue', [
            'book_copy_code' => $copy->copy_code,
            'member_lookup' => $member->member_code,
        ]);

        $response = $this->actingAs($user)->post('/admin/circulation/return', [
            'book_copy_code' => $copy->copy_code,
            'notes' => 'Returned in good condition',
        ]);

        $response->assertRedirect('/admin/circulation');
        $this->assertSame(BookCopy::STATUS_AVAILABLE, $copy->fresh()->status);
        $this->assertSame(0, $member->fresh()->books_borrowed_count);
        $this->assertSame(1, $book->fresh()->available_copies);
        $this->assertSame(BorrowTransaction::STATUS_RETURNED, BorrowTransaction::query()->first()->status);
    }

    public function test_return_book_fails_without_active_transaction(): void
    {
        [$user, $member, $book, $copy] = $this->circulationFixtures();

        $this->actingAs($user)->post('/admin/circulation/return', [
            'book_copy_code' => $copy->copy_code,
        ])->assertSessionHasErrors();

        $this->assertDatabaseCount('borrow_transactions', 0);
        $this->assertSame(BookCopy::STATUS_AVAILABLE, $copy->fresh()->status);
        $this->assertSame(0, $member->fresh()->books_borrowed_count);
        $this->assertSame(1, $book->fresh()->available_copies);
    }

    public function test_repeated_issue_request_does_not_create_duplicate_transaction(): void
    {
        [$user, $member, $book, $copy] = $this->circulationFixtures();

        $payload = [
            'book_copy_code' => $copy->copy_code,
            'member_lookup' => $member->member_code,
        ];

        $this->actingAs($user)->post('/admin/circulation/issue', $payload)->assertRedirect();
        $this->actingAs($user)->post('/admin/circulation/issue', $payload)->assertSessionHasErrors();

        $this->assertDatabaseCount('borrow_transactions', 1);
        $this->assertSame(1, $member->fresh()->books_borrowed_count);
        $this->assertSame(0, $book->fresh()->available_copies);
    }

    public function test_return_rolls_back_when_book_counter_is_inconsistent(): void
    {
        [$user, $member, $book, $copy] = $this->circulationFixtures();

        $this->actingAs($user)->post('/admin/circulation/issue', [
            'book_copy_code' => $copy->copy_code,
            'member_lookup' => $member->member_code,
        ])->assertRedirect();

        Book::query()->whereKey($book->id)->update([
            'available_copies' => $book->total_copies,
        ]);

        $response = $this->actingAs($user)->post('/admin/circulation/return', [
            'book_copy_code' => $copy->copy_code,
        ]);

        $response->assertSessionHasErrors('book_copy_code');

        $this->assertSame(BookCopy::STATUS_BORROWED, $copy->fresh()->status);
        $this->assertSame(1, $member->fresh()->books_borrowed_count);
        $this->assertSame($book->total_copies, $book->fresh()->available_copies);
        $this->assertSame(BorrowTransaction::STATUS_BORROWED, BorrowTransaction::query()->first()->status);
    }

    private function circulationFixtures(
        string $memberState = 'approved',
        int $maxBooks = 3,
        int $borrowedCount = 0,
        string $copyStatus = BookCopy::STATUS_AVAILABLE,
    ): array {
        $user = User::factory()->create(['role' => User::ROLE_LIBRARIAN]);
        $memberCategory = MemberCategory::factory()->create(['max_books' => $maxBooks, 'loan_days' => 14]);
        $memberFactory = Member::factory()->for($memberCategory);

        $member = match ($memberState) {
            'pending' => $memberFactory->pending()->create(),
            'rejected' => $memberFactory->rejected()->create(),
            default => $memberFactory->create(),
        };

        $member->forceFill(['books_borrowed_count' => $borrowedCount])->save();

        $category = BookCategory::factory()->create();
        $book = Book::factory()->create([
            'category_id' => $category->id,
            'total_copies' => 1,
            'available_copies' => $copyStatus === BookCopy::STATUS_AVAILABLE ? 1 : 0,
        ]);
        $copy = BookCopy::factory()->create([
            'book_id' => $book->id,
            'copy_code' => 'LIB-0001-001',
            'status' => $copyStatus,
        ]);

        return [$user, $member, $book, $copy];
    }
}
