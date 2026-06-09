<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Booklist;
use App\Models\DigitalBookAsset;
use App\Models\Member;
use App\Models\User;
use App\Services\DigitalLoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_booklist_requires_member_login(): void
    {
        $this->get('/member/booklist')
            ->assertRedirect(route('member.login'));
    }

    public function test_member_can_add_and_remove_books_from_an_isolated_booklist(): void
    {
        $member = Member::factory()->create();
        $otherMember = Member::factory()->create();
        [$book] = $this->createReadyBook();

        $this->actingAs($member, 'member')
            ->post(route('member.booklist.store', $book))
            ->assertRedirect();
        $this->actingAs($member, 'member')
            ->post(route('member.booklist.store', $book))
            ->assertRedirect();

        Booklist::query()->create([
            'member_id' => $otherMember->id,
            'book_id' => $book->id,
        ]);

        $this->assertSame(2, Booklist::query()->count());

        $this->actingAs($member, 'member')
            ->delete(route('member.booklist.destroy', $book))
            ->assertRedirect();

        $this->assertDatabaseMissing('booklists', [
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);
        $this->assertDatabaseHas('booklists', [
            'member_id' => $otherMember->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_booklist_page_changes_from_borrow_to_read_after_a_digital_loan(): void
    {
        $member = Member::factory()->create();
        [$book] = $this->createReadyBook();
        Booklist::query()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.booklist.index'))
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee('Borrow')
            ->assertDontSee(route('member.reader.open', $book), false);

        app(DigitalLoanService::class)->borrow($member, $book);

        $this->actingAs($member, 'member')
            ->get(route('member.booklist.index'))
            ->assertOk()
            ->assertSee('Read')
            ->assertSee(route('member.reader.open', $book), false);
    }

    public function test_borrowed_page_shows_active_and_returned_loans_with_allowed_actions(): void
    {
        $member = Member::factory()->create();
        [$activeBook] = $this->createReadyBook();
        [$returnedBook] = $this->createReadyBook();
        $service = app(DigitalLoanService::class);
        $activeLoan = $service->borrow($member, $activeBook);
        $returnedLoan = $service->borrow($member, $returnedBook);
        $service->returnLoan($returnedLoan);

        $response = $this->actingAs($member, 'member')
            ->get(route('member.borrowed.index'));

        $response->assertOk()
            ->assertSee('Pinjaman aktif')
            ->assertSee('Riwayat pinjaman')
            ->assertSee($activeBook->title)
            ->assertSee($returnedBook->title)
            ->assertSee(route('member.reader.open', $activeBook), false)
            ->assertSee(route('member.borrowed.return', $activeLoan), false)
            ->assertDontSee(route('member.borrowed.extend', $activeLoan), false);

        $activeLoan->forceFill(['due_at' => now()->addHours(12)])->save();

        $this->actingAs($member, 'member')
            ->get(route('member.borrowed.index'))
            ->assertOk()
            ->assertSee(route('member.borrowed.extend', $activeLoan), false);
    }

    public function test_home_catalog_and_borrowed_pages_show_the_saved_resume_page(): void
    {
        $member = Member::factory()->create();
        [$book] = $this->createReadyBook(['title' => 'Resume Reader']);
        $loan = app(DigitalLoanService::class)->borrow($member, $book);
        $loan->forceFill(['last_read_page' => 6])->save();

        foreach ([route('home'), route('books.search'), route('member.borrowed.index')] as $url) {
            $this->actingAs($member, 'member')
                ->get($url)
                ->assertOk()
                ->assertSee('Lanjutkan Membaca (Hal. 6)');
        }
    }

    public function test_member_cannot_mutate_another_members_digital_loan(): void
    {
        $owner = Member::factory()->create();
        $otherMember = Member::factory()->create();
        [$book] = $this->createReadyBook();
        $loan = app(DigitalLoanService::class)->borrow($owner, $book);
        $loan->forceFill(['due_at' => now()->addHours(12)])->save();

        $this->actingAs($otherMember, 'member')
            ->post(route('member.borrowed.extend', $loan))
            ->assertNotFound();
        $this->actingAs($otherMember, 'member')
            ->delete(route('member.borrowed.return', $loan))
            ->assertNotFound();

        $this->assertNull($loan->fresh()->returned_at);
        $this->assertNull($loan->fresh()->extended_at);
    }

    public function test_catalog_offers_description_and_borrow_before_read(): void
    {
        $member = Member::factory()->create();
        [$book] = $this->createReadyBook([
            'title' => 'Clean Architecture',
            'description' => 'A practical guide to maintainable software.',
        ]);

        $response = $this->actingAs($member, 'member')->get(route('books.search'));

        $response->assertOk()
            ->assertSee('Description')
            ->assertSee($book->description)
            ->assertSee('Borrow')
            ->assertSee(route('member.digital-loans.store', $book), false)
            ->assertDontSee(route('member.reader.open', $book), false);

        app(DigitalLoanService::class)->borrow($member, $book);

        $this->actingAs($member, 'member')
            ->get(route('books.search'))
            ->assertOk()
            ->assertSee('Read')
            ->assertSee(route('member.reader.open', $book), false);
    }

    public function test_guest_sees_borrow_and_booklist_actions_that_lead_to_login(): void
    {
        [$book] = $this->createReadyBook();

        $this->get(route('books.search'))
            ->assertOk()
            ->assertSee('Borrow')
            ->assertSee('Booklist')
            ->assertSee(route('member.login'), false)
            ->assertDontSee(route('member.reader.open', $book), false);
    }

    public function test_logged_in_home_replaces_registration_prompt_with_member_library_links(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member, 'member')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Booklist')
            ->assertSee('Borrowed')
            ->assertSee(route('member.booklist.index'), false)
            ->assertSee(route('member.borrowed.index'), false)
            ->assertDontSee('Register as member');
    }

    public function test_member_navbar_uses_theme_and_profile_icons_without_first_name_text(): void
    {
        $member = Member::factory()->create(['first_name' => 'NavbarSecretName']);

        $this->actingAs($member, 'member')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-lucide="moon"', false)
            ->assertSee('data-lucide="sun"', false)
            ->assertSee('data-lucide="user-round"', false)
            ->assertSee(route('member.booklist.index'), false)
            ->assertSee(route('member.borrowed.index'), false)
            ->assertDontSee('NavbarSecretName')
            ->assertDontSee('x-text="dark ? \'Light\' : \'Dark\'"', false);
    }

    public function test_public_layout_includes_smooth_motion_with_reduced_motion_support(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('scroll-behavior: smooth', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.page-enter', $css);
        $this->assertStringContainsString('class="page-enter"', $layout);
    }

    private function createReadyBook(array $bookAttributes = []): array
    {
        $book = Book::factory()->create(array_merge([
            'total_copies' => 1,
            'available_copies' => 1,
        ], $bookAttributes));
        $copy = BookCopy::factory()->for($book)->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $uuid = fake()->uuid();
        $asset = DigitalBookAsset::query()->create([
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

        return [$book, $copy, $asset];
    }
}
