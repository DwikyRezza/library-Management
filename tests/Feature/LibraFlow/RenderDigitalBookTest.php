<?php

namespace Tests\Feature\LibraFlow;

use App\Contracts\PdfPageRenderer;
use App\Jobs\RenderDigitalBook;
use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RenderDigitalBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_job_marks_the_asset_ready_with_its_page_count(): void
    {
        $asset = $this->createProcessingAsset();
        $renderer = new class implements PdfPageRenderer
        {
            public function render(DigitalBookAsset $asset): int
            {
                return 2;
            }
        };

        (new RenderDigitalBook($asset->id))->handle($renderer);

        $asset->refresh();

        $this->assertSame(DigitalBookAsset::STATUS_READY, $asset->status);
        $this->assertSame(2, $asset->page_count);
        $this->assertSame("digital-books/{$asset->uuid}/pages", $asset->pages_path);
        $this->assertNotNull($asset->rendered_at);
    }

    public function test_render_job_records_a_failed_status_when_renderer_throws(): void
    {
        $asset = $this->createProcessingAsset();
        $renderer = new class implements PdfPageRenderer
        {
            public function render(DigitalBookAsset $asset): int
            {
                throw new RuntimeException('Invalid or encrypted PDF.');
            }
        };

        try {
            (new RenderDigitalBook($asset->id))->handle($renderer);
        } catch (RuntimeException) {
            // Queue retries still need the failure state to be visible to the admin.
        }

        $asset->refresh();

        $this->assertSame(DigitalBookAsset::STATUS_FAILED, $asset->status);
        $this->assertSame('Invalid or encrypted PDF.', $asset->last_error);
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
