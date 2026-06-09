<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Models\Member;
use App\Models\ReadingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDigitalBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_pdf_that_is_immediately_ready_to_read(): void
    {
        config(['services.digital_reader.storage_disk' => 'local']);
        Storage::fake('local');
        Queue::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::factory()->create();
        $pdf = UploadedFile::fake()->createWithContent('clean-code.pdf', $this->minimalPdf());

        $this->actingAs($admin)
            ->post("/admin/books/{$book->id}/digital", ['pdf' => $pdf])
            ->assertRedirect(route('admin.books.show', $book));

        $asset = DigitalBookAsset::query()->whereBelongsTo($book)->firstOrFail();

        $this->assertSame(DigitalBookAsset::STATUS_READY, $asset->status);
        $this->assertSame(0, $asset->page_count);
        $this->assertSame($admin->id, $asset->uploaded_by);
        $this->assertSame('clean-code.pdf', $asset->original_name);
        Storage::disk('local')->assertExists($asset->original_path);
        Queue::assertNothingPushed();
    }

    public function test_librarian_cannot_upload_a_digital_book(): void
    {
        config(['services.digital_reader.storage_disk' => 'local']);
        Storage::fake('local');

        $librarian = User::factory()->create(['role' => User::ROLE_LIBRARIAN]);
        $book = Book::factory()->create();

        $this->actingAs($librarian)
            ->post("/admin/books/{$book->id}/digital", [
                'pdf' => UploadedFile::fake()->createWithContent('book.pdf', $this->minimalPdf()),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('digital_book_assets', 0);
    }

    public function test_admin_upload_rejects_non_pdf_files(): void
    {
        config(['services.digital_reader.storage_disk' => 'local']);
        Storage::fake('local');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $book = Book::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.books.show', $book))
            ->post("/admin/books/{$book->id}/digital", [
                'pdf' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('pdf');

        $this->assertDatabaseCount('digital_book_assets', 0);
    }

    public function test_only_admin_sees_digital_book_management_controls(): void
    {
        $book = Book::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $librarian = User::factory()->create(['role' => User::ROLE_LIBRARIAN]);

        $this->actingAs($admin)
            ->get(route('admin.books.show', $book))
            ->assertOk()
            ->assertSee('Kelola buku digital');

        $this->actingAs($librarian)
            ->get(route('admin.books.show', $book))
            ->assertOk()
            ->assertDontSee('Kelola buku digital');
    }

    public function test_replacing_a_pdf_preserves_existing_reading_history(): void
    {
        config(['services.digital_reader.storage_disk' => 'local']);
        Storage::fake('local');
        Queue::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = Member::factory()->create();
        $book = Book::factory()->create();
        $oldAsset = DigitalBookAsset::query()->create([
            'uuid' => fake()->uuid(),
            'book_id' => $book->id,
            'original_path' => 'digital-books/old/original.pdf',
            'original_name' => 'old.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'sha256' => str_repeat('a', 64),
            'page_count' => 2,
            'status' => DigitalBookAsset::STATUS_READY,
            'uploaded_by' => $admin->id,
            'rendered_at' => now(),
        ]);
        $session = ReadingSession::query()->create([
            'uuid' => fake()->uuid(),
            'member_id' => $member->id,
            'book_id' => $book->id,
            'digital_book_asset_id' => $oldAsset->id,
            'started_at' => now()->subMinute(),
            'last_active_at' => now(),
            'last_page' => 1,
            'max_page' => 1,
            'duration_seconds' => 30,
        ]);

        $this->actingAs($admin)
            ->post("/admin/books/{$book->id}/digital", [
                'pdf' => UploadedFile::fake()->createWithContent('replacement.pdf', $this->minimalPdf()),
            ])
            ->assertRedirect(route('admin.books.show', $book));

        $this->assertDatabaseCount('digital_book_assets', 1);
        $this->assertDatabaseHas('reading_sessions', ['id' => $session->id]);
        $this->assertNull($session->refresh()->digital_book_asset_id);
        $this->assertNotNull($session->ended_at);
    }

    private function minimalPdf(): string
    {
        return <<<'PDF'
%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj
3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 100 100]>>endobj
trailer<</Root 1 0 R>>
%%EOF
PDF;
    }
}
