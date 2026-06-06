<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Models\Member;
use App\Models\ReadingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_private_reading_history_and_session_details(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = Member::factory()->create([
            'first_name' => 'Alya',
            'last_name' => 'Putri',
            'member_code' => 'MBR-ALYA',
        ]);
        $book = Book::factory()->create(['title' => 'Arsitektur Laravel']);
        $session = $this->createSession($member, $book);

        $this->actingAs($admin)
            ->get('/admin/reading-history')
            ->assertOk()
            ->assertSee('Alya Putri')
            ->assertSee('Arsitektur Laravel')
            ->assertSee('MBR-ALYA');

        $this->actingAs($admin)
            ->get("/admin/reading-history/{$session->uuid}")
            ->assertOk()
            ->assertSee('203.0.113.10')
            ->assertSee('Test Browser 1.0')
            ->assertSee('120')
            ->assertSee('3');
    }

    public function test_librarian_cannot_view_reading_history(): void
    {
        $librarian = User::factory()->create(['role' => User::ROLE_LIBRARIAN]);

        $this->actingAs($librarian)
            ->get('/admin/reading-history')
            ->assertForbidden();
    }

    private function createSession(Member $member, Book $book): ReadingSession
    {
        $uploader = User::factory()->create(['role' => User::ROLE_ADMIN]);
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
            'page_count' => 8,
            'status' => DigitalBookAsset::STATUS_READY,
            'uploaded_by' => $uploader->id,
            'rendered_at' => now(),
        ]);

        return ReadingSession::query()->create([
            'uuid' => fake()->uuid(),
            'member_id' => $member->id,
            'book_id' => $book->id,
            'digital_book_asset_id' => $asset->id,
            'started_at' => now()->subMinutes(5),
            'last_active_at' => now(),
            'last_page' => 3,
            'max_page' => 3,
            'duration_seconds' => 120,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Test Browser 1.0',
        ]);
    }
}
