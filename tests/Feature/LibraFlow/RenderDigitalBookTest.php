<?php

namespace Tests\Feature\LibraFlow;

use App\Jobs\RenderDigitalBook;
use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenderDigitalBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_render_job_marks_the_asset_ready_without_rendering_pages(): void
    {
        $asset = $this->createProcessingAsset();

        (new RenderDigitalBook($asset->id))->handle();

        $asset->refresh();

        $this->assertSame(DigitalBookAsset::STATUS_READY, $asset->status);
        $this->assertSame(0, $asset->page_count);
        $this->assertNull($asset->pages_path);
        $this->assertNull($asset->last_error);
        $this->assertNotNull($asset->rendered_at);
    }

    public function test_legacy_render_job_ignores_an_asset_that_was_already_deleted(): void
    {
        (new RenderDigitalBook(999999))->handle();

        $this->assertDatabaseCount('digital_book_assets', 0);
    }

    private function createProcessingAsset(): DigitalBookAsset
    {
        $book = Book::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        return DigitalBookAsset::query()->create([
            'uuid' => fake()->uuid(),
            'book_id' => $book->id,
            'original_path' => 'digital-books/source/original.pdf',
            'original_name' => 'book.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'sha256' => str_repeat('a', 64),
            'status' => DigitalBookAsset::STATUS_PROCESSING,
            'uploaded_by' => $admin->id,
        ]);
    }
}
