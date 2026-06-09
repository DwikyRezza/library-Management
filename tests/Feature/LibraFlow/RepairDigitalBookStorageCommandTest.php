<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RepairDigitalBookStorageCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_copies_a_legacy_local_pdf_to_the_configured_disk(): void
    {
        config(['services.digital_reader.storage_disk' => 's3']);
        Storage::fake('local');
        Storage::fake('s3');

        $asset = $this->createAsset('Buku Lokal Lama');
        Storage::disk('local')->put($asset->original_path, $this->minimalPdf());

        $this->artisan('digital-books:repair-storage')
            ->expectsOutputToContain('Buku Lokal Lama: local -> s3')
            ->assertSuccessful();

        Storage::disk('local')->assertExists($asset->original_path);
        Storage::disk('s3')->assertExists($asset->original_path);
        $this->assertSame('s3', $asset->fresh()->storage_disk);
    }

    public function test_command_backfills_the_disk_when_the_pdf_is_already_available(): void
    {
        config(['services.digital_reader.storage_disk' => 's3']);
        Storage::fake('local');
        Storage::fake('s3');

        $asset = $this->createAsset('Buku Sudah di S3');
        Storage::disk('s3')->put($asset->original_path, $this->minimalPdf());

        $this->artisan('digital-books:repair-storage')
            ->expectsOutputToContain('Buku Sudah di S3: tersedia di s3')
            ->assertSuccessful();

        Storage::disk('s3')->assertExists($asset->original_path);
        $this->assertSame('s3', $asset->fresh()->storage_disk);
    }

    public function test_command_fails_and_reports_an_asset_missing_from_all_candidate_disks(): void
    {
        config(['services.digital_reader.storage_disk' => 's3']);
        Storage::fake('local');
        Storage::fake('s3');

        $asset = $this->createAsset('Buku Hilang');

        $this->artisan('digital-books:repair-storage')
            ->expectsOutputToContain('Buku Hilang: file PDF tidak ditemukan')
            ->assertFailed();

        Storage::disk('local')->assertMissing($asset->original_path);
        Storage::disk('s3')->assertMissing($asset->original_path);
        $this->assertNull($asset->fresh()->storage_disk);
    }

    private function createAsset(string $title): DigitalBookAsset
    {
        $book = Book::factory()->create(['title' => $title]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $uuid = fake()->uuid();

        return DigitalBookAsset::query()->create([
            'uuid' => $uuid,
            'book_id' => $book->id,
            'original_path' => "digital-books/{$uuid}/original.pdf",
            'original_name' => 'book.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => strlen($this->minimalPdf()),
            'sha256' => hash('sha256', $this->minimalPdf()),
            'page_count' => 0,
            'status' => DigitalBookAsset::STATUS_READY,
            'uploaded_by' => $admin->id,
            'rendered_at' => now(),
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
