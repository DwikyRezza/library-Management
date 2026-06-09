<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Booklist;
use App\Models\DigitalBookAsset;
use App\Models\DigitalLoan;
use App\Models\Member;
use App\Models\ReadingSession;
use App\Models\User;
use App\Services\DigitalLoanService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalLoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_loan_relates_a_member_book_and_copy(): void
    {
        $member = Member::factory()->create();
        $book = Book::factory()->create();
        $copy = BookCopy::factory()->for($book)->create();

        $loan = DigitalLoan::query()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'borrowed_at' => now(),
            'due_at' => now()->addDays(10),
        ]);

        $this->assertTrue($loan->member->is($member));
        $this->assertTrue($loan->book->is($book));
        $this->assertTrue($loan->bookCopy->is($copy));
        $this->assertTrue($loan->is_active);
        $this->assertTrue($loan->can_extend === false);
    }

    public function test_booklist_pair_is_unique_per_member_and_book(): void
    {
        $member = Member::factory()->create();
        $book = Book::factory()->create();

        Booklist::query()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        $this->expectException(QueryException::class);

        Booklist::query()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_digital_loan_factory_links_the_copy_to_the_same_book(): void
    {
        $loan = DigitalLoan::factory()->create();

        $this->assertSame($loan->book_id, $loan->bookCopy->book_id);
    }

    public function test_approved_member_can_borrow_an_available_digital_book_for_ten_days(): void
    {
        Carbon::setTestNow('2026-06-09 10:00:00');

        $member = Member::factory()->create();
        [$book, $copy] = $this->createReadyBookWithCopy();

        $this->actingAs($member, 'member')
            ->post(route('member.digital-loans.store', $book))
            ->assertRedirect(route('member.borrowed.index'));

        $loan = DigitalLoan::query()->firstOrFail();

        $this->assertSame($member->id, $loan->member_id);
        $this->assertSame($book->id, $loan->book_id);
        $this->assertSame($copy->id, $loan->book_copy_id);
        $this->assertTrue($loan->borrowed_at->equalTo(now()));
        $this->assertTrue($loan->due_at->equalTo(now()->addDays(10)));
        $this->assertSame(0, $book->fresh()->available_copies);
        $this->assertSame(BookCopy::STATUS_BORROWED, $copy->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_pending_member_cannot_borrow_a_digital_book(): void
    {
        $member = Member::factory()->pending()->create();
        [$book] = $this->createReadyBookWithCopy();

        $this->actingAs($member, 'member')
            ->from(route('books.search'))
            ->post(route('member.digital-loans.store', $book))
            ->assertRedirect(route('books.search'))
            ->assertSessionHasErrors('book');

        $this->assertDatabaseCount('digital_loans', 0);
    }

    public function test_member_cannot_borrow_without_a_ready_pdf_or_available_copy(): void
    {
        $member = Member::factory()->create();
        $bookWithoutPdf = Book::factory()->create([
            'total_copies' => 1,
            'available_copies' => 1,
        ]);
        BookCopy::factory()->for($bookWithoutPdf)->create();

        $this->actingAs($member, 'member')
            ->post(route('member.digital-loans.store', $bookWithoutPdf))
            ->assertSessionHasErrors('book');

        [$unavailableBook] = $this->createReadyBookWithCopy(BookCopy::STATUS_BORROWED);

        $this->actingAs($member, 'member')
            ->post(route('member.digital-loans.store', $unavailableBook))
            ->assertSessionHasErrors('book');

        $this->assertDatabaseCount('digital_loans', 0);
    }

    public function test_member_cannot_borrow_the_same_book_twice(): void
    {
        $member = Member::factory()->create();
        [$book] = $this->createReadyBookWithCopy();

        $this->actingAs($member, 'member')
            ->post(route('member.digital-loans.store', $book))
            ->assertRedirect(route('member.borrowed.index'));

        $secondCopy = BookCopy::factory()->for($book)->create();
        $book->forceFill(['total_copies' => 2, 'available_copies' => 1])->save();

        $this->actingAs($member, 'member')
            ->post(route('member.digital-loans.store', $book))
            ->assertSessionHasErrors('book');

        $this->assertSame(BookCopy::STATUS_AVAILABLE, $secondCopy->fresh()->status);
        $this->assertDatabaseCount('digital_loans', 1);
    }

    public function test_member_has_a_separate_limit_of_three_active_digital_loans(): void
    {
        $member = Member::factory()->create();

        foreach (range(1, 3) as $index) {
            [$book] = $this->createReadyBookWithCopy();

            $this->actingAs($member, 'member')
                ->post(route('member.digital-loans.store', $book))
                ->assertRedirect(route('member.borrowed.index'));
        }

        [$fourthBook, $fourthCopy] = $this->createReadyBookWithCopy();

        $this->actingAs($member, 'member')
            ->post(route('member.digital-loans.store', $fourthBook))
            ->assertSessionHasErrors('book');

        $this->assertSame(BookCopy::STATUS_AVAILABLE, $fourthCopy->fresh()->status);
        $this->assertSame(3, DigitalLoan::query()->active()->count());
    }

    public function test_borrow_synchronizes_an_expired_loan_before_creating_a_new_one(): void
    {
        $member = Member::factory()->create();
        [$book, $copy] = $this->createReadyBookWithCopy();
        $service = app(DigitalLoanService::class);
        $expiredLoan = $service->borrow($member, $book);
        $expiredLoan->forceFill(['due_at' => now()->subMinute()])->save();

        $newLoan = $service->borrow($member, $book);

        $this->assertSame(DigitalLoan::RETURN_EXPIRED, $expiredLoan->fresh()->return_reason);
        $this->assertNotNull($expiredLoan->fresh()->returned_at);
        $this->assertTrue($newLoan->is_active);
        $this->assertSame($copy->id, $newLoan->book_copy_id);
        $this->assertSame(BookCopy::STATUS_BORROWED, $copy->fresh()->status);
        $this->assertSame(0, $book->fresh()->available_copies);
    }

    public function test_digital_loan_can_only_be_extended_once_during_its_final_day(): void
    {
        Carbon::setTestNow('2026-06-09 10:00:00');

        $member = Member::factory()->create();
        [$book] = $this->createReadyBookWithCopy();
        $this->actingAs($member, 'member')->post(route('member.digital-loans.store', $book));
        $loan = DigitalLoan::query()->firstOrFail();

        $this->actingAs($member, 'member')
            ->post(route('member.borrowed.extend', $loan))
            ->assertSessionHasErrors('loan');

        $originalDueAt = now()->addHours(23);
        $loan->forceFill(['due_at' => $originalDueAt])->save();

        $this->actingAs($member, 'member')
            ->post(route('member.borrowed.extend', $loan))
            ->assertRedirect(route('member.borrowed.index'));

        $loan->refresh();
        $this->assertTrue($loan->extended_at->equalTo(now()));
        $this->assertTrue($loan->due_at->equalTo($originalDueAt->copy()->addDays(10)));

        $this->actingAs($member, 'member')
            ->post(route('member.borrowed.extend', $loan))
            ->assertSessionHasErrors('loan');

        Carbon::setTestNow();
    }

    public function test_manual_return_restores_inventory_and_closes_the_reading_session(): void
    {
        $member = Member::factory()->create();
        [$book, $copy, $asset] = $this->createReadyBookWithCopy();
        $this->actingAs($member, 'member')->post(route('member.digital-loans.store', $book));
        $loan = DigitalLoan::query()->firstOrFail();
        $session = $this->createReadingSession($member, $book, $asset);

        $this->actingAs($member, 'member')
            ->delete(route('member.borrowed.return', $loan))
            ->assertRedirect(route('member.borrowed.index'));

        $this->assertSame(DigitalLoan::RETURN_MANUAL, $loan->fresh()->return_reason);
        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame(BookCopy::STATUS_AVAILABLE, $copy->fresh()->status);
        $this->assertSame(1, $book->fresh()->available_copies);
        $this->assertNotNull($session->fresh()->ended_at);
    }

    public function test_expire_command_returns_due_loans_and_closes_reading_sessions(): void
    {
        Carbon::setTestNow('2026-06-20 10:00:00');

        $member = Member::factory()->create();
        [$book, $copy, $asset] = $this->createReadyBookWithCopy();
        $this->actingAs($member, 'member')->post(route('member.digital-loans.store', $book));
        $loan = DigitalLoan::query()->firstOrFail();
        $loan->forceFill(['due_at' => now()->subMinute()])->save();
        $session = $this->createReadingSession($member, $book, $asset);

        $this->artisan('digital-loans:expire')
            ->expectsOutput('1 pinjaman digital kedaluwarsa telah dikembalikan.')
            ->assertSuccessful();

        $this->assertSame(DigitalLoan::RETURN_EXPIRED, $loan->fresh()->return_reason);
        $this->assertNotNull($loan->fresh()->returned_at);
        $this->assertSame(BookCopy::STATUS_AVAILABLE, $copy->fresh()->status);
        $this->assertSame(1, $book->fresh()->available_copies);
        $this->assertNotNull($session->fresh()->ended_at);

        Carbon::setTestNow();
    }

    private function createReadyBookWithCopy(string $copyStatus = BookCopy::STATUS_AVAILABLE): array
    {
        $availableCopies = $copyStatus === BookCopy::STATUS_AVAILABLE ? 1 : 0;
        $book = Book::factory()->create([
            'total_copies' => 1,
            'available_copies' => $availableCopies,
        ]);
        $copy = BookCopy::factory()->for($book)->create(['status' => $copyStatus]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $uuid = fake()->uuid();

        DigitalBookAsset::query()->create([
            'uuid' => $uuid,
            'book_id' => $book->id,
            'original_path' => "digital-books/{$uuid}/original.pdf",
            'original_name' => 'book.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'sha256' => str_repeat('a', 64),
            'page_count' => 3,
            'status' => DigitalBookAsset::STATUS_READY,
            'uploaded_by' => $admin->id,
            'rendered_at' => now(),
        ]);

        return [$book, $copy, $book->digitalAsset];
    }

    private function createReadingSession(Member $member, Book $book, DigitalBookAsset $asset): ReadingSession
    {
        return ReadingSession::query()->create([
            'uuid' => fake()->uuid(),
            'member_id' => $member->id,
            'book_id' => $book->id,
            'digital_book_asset_id' => $asset->id,
            'started_at' => now(),
            'last_active_at' => now(),
            'last_page' => 1,
            'max_page' => 1,
            'duration_seconds' => 0,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);
    }
}
