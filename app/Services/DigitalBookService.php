<?php

namespace App\Services;

use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DigitalBookService
{
    public const REPAIR_AVAILABLE = 'available';

    public const REPAIR_COPIED = 'copied';

    public const REPAIR_MISSING = 'missing';

    public function replace(Book $book, UploadedFile $pdf, User $uploader): DigitalBookAsset
    {
        $uuid = (string) Str::uuid();
        $directory = "digital-books/{$uuid}";
        $diskName = $this->diskName();
        $disk = Storage::disk($diskName);
        $path = $disk->putFileAs($directory, $pdf, 'original.pdf');

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan PDF buku digital. Periksa konfigurasi storage.');
        }

        try {
            $previous = $book->digitalAsset()->first();

            $asset = DB::transaction(function () use ($book, $pdf, $uploader, $uuid, $path, $diskName, $previous): DigitalBookAsset {
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
                    'storage_disk' => $diskName,
                    'original_name' => $pdf->getClientOriginalName(),
                    'mime_type' => 'application/pdf',
                    'file_size' => $pdf->getSize(),
                    'sha256' => hash_file('sha256', $pdf->getRealPath()),
                    'page_count' => 0,
                    'status' => DigitalBookAsset::STATUS_READY,
                    'uploaded_by' => $uploader->id,
                    'rendered_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            $disk->deleteDirectory($directory);

            throw $exception;
        }

        if ($previous) {
            $this->deleteAssetFiles($previous);
        }

        return $asset;
    }

    public function delete(DigitalBookAsset $asset): void
    {
        DB::transaction(function () use ($asset): void {
            $asset->readingSessions()
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);
            $asset->delete();
        });
        $this->deleteAssetFiles($asset);
    }

    public function readStream(DigitalBookAsset $asset): mixed
    {
        foreach ($this->candidateDiskNames($asset) as $diskName) {
            try {
                $stream = Storage::disk($diskName)->readStream($asset->original_path);
            } catch (Throwable $exception) {
                Log::warning('Digital book file could not be read from storage.', [
                    'digital_book_asset_id' => $asset->id,
                    'disk' => $diskName,
                    'path' => $asset->original_path,
                    'exception' => $exception::class,
                ]);

                continue;
            }

            if (! is_resource($stream)) {
                continue;
            }

            if (blank($asset->storage_disk)) {
                DigitalBookAsset::query()
                    ->whereKey($asset->id)
                    ->whereNull('storage_disk')
                    ->update(['storage_disk' => $diskName]);
            }

            return $stream;
        }

        return false;
    }

    public function repairStorage(DigitalBookAsset $asset): array
    {
        $targetDiskName = $this->diskName();

        foreach ($this->repairCandidateDiskNames($asset) as $sourceDiskName) {
            $stream = $this->readStreamFromDisk($asset, $sourceDiskName);

            if (! is_resource($stream)) {
                continue;
            }

            if ($sourceDiskName === $targetDiskName) {
                fclose($stream);
                $this->recordStorageDisk($asset, $targetDiskName);

                return [
                    'status' => self::REPAIR_AVAILABLE,
                    'source_disk' => $sourceDiskName,
                    'target_disk' => $targetDiskName,
                ];
            }

            try {
                $stored = Storage::disk($targetDiskName)->put($asset->original_path, $stream);
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                throw new RuntimeException(
                    "Gagal menyalin PDF ke disk {$targetDiskName}."
                );
            }

            $this->recordStorageDisk($asset, $targetDiskName);

            return [
                'status' => self::REPAIR_COPIED,
                'source_disk' => $sourceDiskName,
                'target_disk' => $targetDiskName,
            ];
        }

        return [
            'status' => self::REPAIR_MISSING,
            'source_disk' => null,
            'target_disk' => $targetDiskName,
        ];
    }

    private function diskName(): string
    {
        return (string) config('services.digital_reader.storage_disk', config('filesystems.default', 'local'));
    }

    private function candidateDiskNames(DigitalBookAsset $asset): array
    {
        if (filled($asset->storage_disk)) {
            return [(string) $asset->storage_disk];
        }

        return array_values(array_unique([
            $this->diskName(),
            'local',
        ]));
    }

    private function repairCandidateDiskNames(DigitalBookAsset $asset): array
    {
        return array_values(array_unique(array_filter([
            $this->diskName(),
            $asset->storage_disk,
            'local',
        ])));
    }

    private function readStreamFromDisk(DigitalBookAsset $asset, string $diskName): mixed
    {
        try {
            return Storage::disk($diskName)->readStream($asset->original_path);
        } catch (Throwable $exception) {
            Log::warning('Digital book file could not be inspected during storage repair.', [
                'digital_book_asset_id' => $asset->id,
                'disk' => $diskName,
                'path' => $asset->original_path,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    private function recordStorageDisk(DigitalBookAsset $asset, string $diskName): void
    {
        $asset->forceFill(['storage_disk' => $diskName])->save();
    }

    private function deleteAssetFiles(DigitalBookAsset $asset, ?string $directory = null): void
    {
        $directory ??= str_replace('\\', '/', dirname($asset->original_path));

        foreach ($this->candidateDiskNames($asset) as $diskName) {
            try {
                Storage::disk($diskName)->deleteDirectory($directory);
            } catch (Throwable $exception) {
                Log::warning('Digital book directory could not be deleted from storage.', [
                    'digital_book_asset_id' => $asset->id,
                    'disk' => $diskName,
                    'directory' => $directory,
                    'exception' => $exception::class,
                ]);
            }
        }
    }
}
