<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Models\Member;
use App\Models\ReadingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_member_login_before_reading(): void
    {
        $book = Book::factory()->create();

        $this->get("/read/{$book->id}")
            ->assertRedirect(route('member.login'));
    }

    public function test_pending_member_can_open_a_ready_digital_book(): void
    {
        $member = Member::factory()->pending()->create();
        [$book] = $this->createReadyBook();

        $response = $this->actingAs($member, 'member')->get("/read/{$book->id}");

        $session = ReadingSession::query()->whereBelongsTo($member)->firstOrFail();

        $response->assertRedirect(route('member.reader.show', $session));
        $this->assertSame($book->id, $session->book_id);
        $this->assertSame(1, $session->last_page);
    }

    public function test_rejected_member_is_logged_out_before_reading(): void
    {
        $member = Member::factory()->rejected()->create();
        [$book] = $this->createReadyBook();

        $this->actingAs($member, 'member')
            ->get("/read/{$book->id}")
            ->assertRedirect(route('member.login'));

        $this->assertGuest('member');
        $this->assertDatabaseCount('reading_sessions', 0);
    }

    public function test_only_the_session_owner_can_open_the_reader(): void
    {
        $owner = Member::factory()->pending()->create();
        $otherMember = Member::factory()->create();
        [$book, $asset] = $this->createReadyBook();
        $session = $this->createReadingSession($owner, $book, $asset);

        $this->actingAs($otherMember, 'member')
            ->get(route('member.reader.show', $session))
            ->assertNotFound();
    }

    public function test_session_owner_can_open_the_reader_interface(): void
    {
        $member = Member::factory()->pending()->create();
        [$book, $asset] = $this->createReadyBook();
        $session = $this->createReadingSession($member, $book, $asset);

        $this->actingAs($member, 'member')
            ->get(route('member.reader.show', $session))
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee('readerCanvas')
            ->assertSee('/document', false)
            ->assertSee('Dokumen gagal dimuat')
            ->assertDontSee('watermark');
    }

    public function test_document_response_is_a_private_pdf_for_the_session_owner(): void
    {
        config(['services.digital_reader.storage_disk' => 'local']);
        Storage::fake('local');

        $member = Member::factory()->pending()->create();
        [$book, $asset] = $this->createReadyBook();
        $session = $this->createReadingSession($member, $book, $asset);
        Storage::disk('local')->put($asset->original_path, $this->minimalPdf());

        $response = $this->actingAs($member, 'member')
            ->get("/reader/{$session->uuid}/document");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $cacheControl = $response->headers->get('cache-control', '');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringNotContainsString('public', $cacheControl);
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition', ''));
        $this->assertSame($this->minimalPdf(), $response->streamedContent());
    }

    public function test_document_response_is_not_found_for_another_member(): void
    {
        config(['services.digital_reader.storage_disk' => 'local']);
        Storage::fake('local');

        $owner = Member::factory()->pending()->create();
        $otherMember = Member::factory()->pending()->create();
        [$book, $asset] = $this->createReadyBook();
        $session = $this->createReadingSession($owner, $book, $asset);
        Storage::disk('local')->put($asset->original_path, $this->minimalPdf());

        $this->actingAs($otherMember, 'member')
            ->get("/reader/{$session->uuid}/document")
            ->assertNotFound();
    }

    public function test_document_response_can_stream_a_pdf_from_the_configured_disk(): void
    {
        config(['services.digital_reader.storage_disk' => 's3']);
        Storage::fake('s3');

        $member = Member::factory()->pending()->create();
        [$book, $asset] = $this->createReadyBook();
        $session = $this->createReadingSession($member, $book, $asset);
        Storage::disk('s3')->put($asset->original_path, $this->minimalPdf());

        $response = $this->actingAs($member, 'member')
            ->get("/reader/{$session->uuid}/document");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertSame($this->minimalPdf(), $response->streamedContent());
    }

    public function test_heartbeat_updates_last_page_and_caps_recorded_duration(): void
    {
        Carbon::setTestNow('2026-06-06 10:00:00');

        $member = Member::factory()->pending()->create();
        [$book, $asset] = $this->createReadyBook();
        $session = $this->createReadingSession($member, $book, $asset);

        Carbon::setTestNow('2026-06-06 10:02:00');

        $this->actingAs($member, 'member')
            ->postJson(route('member.reader.heartbeat', $session), ['page' => 2])
            ->assertOk()
            ->assertJsonPath('lastPage', 2)
            ->assertJsonPath('durationSeconds', 60);

        $session->refresh();

        $this->assertSame(2, $session->last_page);
        $this->assertSame(2, $session->max_page);
        $this->assertSame(60, $session->duration_seconds);

        Carbon::setTestNow();
    }

    public function test_heartbeat_records_the_page_count_reported_by_pdfjs(): void
    {
        $member = Member::factory()->pending()->create();
        [$book, $asset] = $this->createReadyBook();
        $asset->forceFill(['page_count' => 0])->save();
        $session = $this->createReadingSession($member, $book, $asset);

        $this->actingAs($member, 'member')
            ->postJson(route('member.reader.heartbeat', $session), [
                'page' => 2,
                'total_pages' => 324,
            ])
            ->assertOk()
            ->assertJsonPath('lastPage', 2);

        $this->assertSame(324, $asset->refresh()->page_count);
    }

    public function test_catalog_only_offers_online_reading_for_ready_assets(): void
    {
        $member = Member::factory()->create();
        [$readyBook] = $this->createReadyBook();
        $processingBook = Book::factory()->create(['title' => 'Belum Siap']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        DigitalBookAsset::query()->create([
            'uuid' => fake()->uuid(),
            'book_id' => $processingBook->id,
            'original_path' => 'digital-books/processing/original.pdf',
            'original_name' => 'processing.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'sha256' => str_repeat('b', 64),
            'page_count' => 0,
            'status' => DigitalBookAsset::STATUS_PROCESSING,
            'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($member, 'member')->get('/books/search');

        $response->assertOk();
        $response->assertSee(route('member.reader.open', $readyBook), false);
        $response->assertDontSee(route('member.reader.open', $processingBook), false);
        $response->assertSee('Sedang diproses');
    }

    public function test_catalog_offers_reading_to_guests_and_redirects_them_to_member_login(): void
    {
        [$readyBook] = $this->createReadyBook();

        $response = $this->get('/books/search');

        $response->assertOk();
        $response->assertSee(route('member.reader.open', $readyBook), false);
        $response->assertSee('Read');

        $this->get(route('member.reader.open', $readyBook))
            ->assertRedirect(route('member.login'));
    }

    private function createReadyBook(): array
    {
        $book = Book::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $uuid = fake()->uuid();
        $asset = DigitalBookAsset::query()->create([
            'uuid' => $uuid,
            'book_id' => $book->id,
            'original_path' => "digital-books/{$uuid}/original.pdf",
            'pages_path' => "digital-books/{$uuid}/pages",
            'original_name' => 'book.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'sha256' => str_repeat('a', 64),
            'page_count' => 3,
            'status' => DigitalBookAsset::STATUS_READY,
            'uploaded_by' => $admin->id,
            'rendered_at' => now(),
        ]);

        return [$book, $asset];
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
