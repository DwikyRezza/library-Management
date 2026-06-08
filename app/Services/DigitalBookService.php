<?php

namespace App\Services;

use App\Jobs\RenderDigitalBook;
use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DigitalBookService
{
    public function replace(Book $book, UploadedFile $pdf, User $uploader): DigitalBookAsset
    {
        $uuid = (string) Str::uuid();
        $directory = "digital-books/{$uuid}";
        $disk = Storage::disk($this->diskName());
        $path = $disk->putFileAs($directory, $pdf, 'original.pdf');

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan PDF buku digital. Periksa konfigurasi storage.');
        }

        try {
            $previous = $book->digitalAsset()->first();

            $asset = DB::transaction(function () use ($book, $pdf, $uploader, $uuid, $path, $previous): DigitalBookAsset {
                if ($previous) {
                    $previous->readingSessions()
                        ->whereNull('ended_at')
                        ->update(['ended_at' => now()]);
                    $previous->delete();
                }

                return DigitalBookAsset::query()->create([
                    'uuid' => $uuid,
                    'book_id' => $book->id,
                    'original_path' => $path,
                    'original_name' => $pdf->getClientOriginalName(),
                    'mime_type' => 'application/pdf',
                    'file_size' => $pdf->getSize(),
                    'sha256' => hash_file('sha256', $pdf->getRealPath()),
                    'status' => DigitalBookAsset::STATUS_PROCESSING,
                    'uploaded_by' => $uploader->id,
                ]);
            });
        } catch (Throwable $exception) {
            $disk->deleteDirectory($directory);

            throw $exception;
        }

        if ($previous) {
            $disk->deleteDirectory("digital-books/{$previous->uuid}");
        }

        RenderDigitalBook::dispatch($asset->id)->afterCommit();

        return $asset;
    }

    public function delete(DigitalBookAsset $asset): void
    {
        $directory = "digital-books/{$asset->uuid}";

        DB::transaction(function () use ($asset): void {
            $asset->readingSessions()
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);
            $asset->delete();
        });
        Storage::disk($this->diskName())->deleteDirectory($directory);
    }

    private function diskName(): string
    {
        return (string) config('services.digital_reader.storage_disk', config('filesystems.default', 'local'));
    }
}
