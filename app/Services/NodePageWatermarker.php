<?php

namespace App\Services;

use App\Contracts\PageWatermarker;
use App\Models\DigitalBookAsset;
use App\Models\Member;
use App\Models\ReadingSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class NodePageWatermarker implements PageWatermarker
{
    public function watermark(
        DigitalBookAsset $asset,
        Member $member,
        ReadingSession $session,
        int $page
    ): string {
        $filename = 'page-'.str_pad((string) $page, 4, '0', STR_PAD_LEFT).'.png';
        $sourcePath = "{$asset->pages_path}/{$filename}";
        $targetPath = "digital-books/{$asset->uuid}/watermarked/{$session->uuid}/{$filename}";
        $disk = Storage::disk($this->diskName());

        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException('Halaman buku digital tidak ditemukan.');
        }

        if ($disk->exists($targetPath)) {
            return $targetPath;
        }

        return Cache::lock("watermark:{$session->uuid}:{$page}", 30)->block(10, function () use (
            $asset,
            $page,
            $member,
            $session,
            $sourcePath,
            $targetPath,
            $disk,
            $filename // <--- SEKARANG SUDAH DITAMBAHKAN DI SINI, WIK!
        ): string {
            if ($disk->exists($targetPath)) {
                return $targetPath;
            }

            $temporaryDirectory = storage_path('app/private/digital-watermark/'.$asset->uuid.'-'.$page.'-'.Str::uuid());
            $sourceTemporaryPath = $temporaryDirectory.'/'.$filename;
            $targetTemporaryPath = $temporaryDirectory.'/watermarked-'.$filename;

            File::ensureDirectoryExists($temporaryDirectory);

            try {
                $this->copyFromDisk($sourcePath, $sourceTemporaryPath);

                $process = new Process([
                    (string) config('services.digital_reader.node_binary', 'node'),
                    base_path('scripts/watermark-page.mjs'),
                    $sourceTemporaryPath,
                    $targetTemporaryPath,
                ]);
                $process->setInput(json_encode([
                    'lines' => [
                        $member->full_name.' | '.$member->member_code,
                        $member->email,
                        'Sesi '.$session->started_at->format('Y-m-d H:i:s T'),
                    ],
                ], JSON_THROW_ON_ERROR));
                $process->setTimeout((int) config('services.digital_reader.watermark_timeout', 60));
                $process->mustRun();

                $targetStream = fopen($targetTemporaryPath, 'rb');

                if (! is_resource($targetStream)) {
                    throw new RuntimeException('Gagal membaca hasil watermark.');
                }

                try {
                    $stored = $disk->put($targetPath, $targetStream);
                } finally {
                    fclose($targetStream);
                }

                if ($stored === false) {
                    throw new RuntimeException('Gagal menyimpan hasil watermark.');
                }
            } finally {
                File::deleteDirectory($temporaryDirectory);
            }

            return $targetPath;
        });
    }

    private function copyFromDisk(string $sourcePath, string $targetPath): void
    {
        $stream = Storage::disk($this->diskName())->readStream($sourcePath);

        if (! is_resource($stream)) {
            throw new RuntimeException('Halaman buku digital tidak ditemukan.');
        }

        $target = fopen($targetPath, 'wb');

        if (! is_resource($target)) {
            fclose($stream);

            throw new RuntimeException('Gagal menyiapkan file sementara halaman.');
        }

        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            fclose($target);
            fclose($stream);
        }
    }

    private function diskName(): string
    {
        return (string) config('services.digital_reader.storage_disk', config('filesystems.default', 'local'));
    }
}