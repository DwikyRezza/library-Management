<?php

namespace App\Services;

use App\Contracts\PdfPageRenderer;
use App\Models\DigitalBookAsset;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class NodePdfPageRenderer implements PdfPageRenderer
{
    public function render(DigitalBookAsset $asset): int
    {
        $disk = Storage::disk($this->diskName());
        $temporaryDirectory = storage_path('app/private/digital-render/'.$asset->uuid.'-'.Str::uuid());
        $inputPath = $temporaryDirectory.'/original.pdf';
        $outputPath = $temporaryDirectory.'/pages';
        $pagesPath = "digital-books/{$asset->uuid}/pages";

        File::ensureDirectoryExists($outputPath);

        try {
            $this->copyFromDisk($asset->original_path, $inputPath);

            $process = new Process([
                (string) config('services.digital_reader.node_binary', 'node'),
                base_path('scripts/render-pdf.mjs'),
                $inputPath,
                $outputPath,
                (string) config('services.digital_reader.render_scale', 1.6),
            ]);
            $process->setTimeout((int) config('services.digital_reader.render_timeout', 600));
            $process->mustRun();

            $result = json_decode(trim($process->getOutput()), true);
            $pageCount = (int) ($result['pageCount'] ?? 0);

            if ($pageCount < 1) {
                throw new RuntimeException('Renderer tidak menghasilkan halaman PDF.');
            }

            $disk->deleteDirectory($pagesPath);

            for ($page = 1; $page <= $pageCount; $page++) {
                $filename = 'page-'.str_pad((string) $page, 4, '0', STR_PAD_LEFT).'.png';
                $localPage = "{$outputPath}/{$filename}";

                if (! is_file($localPage)) {
                    throw new RuntimeException("Renderer tidak menghasilkan {$filename}.");
                }

                $pageStream = fopen($localPage, 'rb');

                if (! is_resource($pageStream)) {
                    throw new RuntimeException("Gagal membaca hasil render {$filename}.");
                }

                try {
                    $stored = $disk->put("{$pagesPath}/{$filename}", $pageStream);
                } finally {
                    fclose($pageStream);
                }

                if ($stored === false) {
                    throw new RuntimeException("Gagal menyimpan hasil render {$filename}.");
                }
            }

            return $pageCount;
        } finally {
            File::deleteDirectory($temporaryDirectory);
        }
    }

    private function copyFromDisk(string $sourcePath, string $targetPath): void
    {
        $stream = Storage::disk($this->diskName())->readStream($sourcePath);

        if (! is_resource($stream)) {
            throw new RuntimeException('PDF asli tidak ditemukan di storage.');
        }

        $target = fopen($targetPath, 'wb');

        if (! is_resource($target)) {
            fclose($stream);

            throw new RuntimeException('Gagal menyiapkan file sementara PDF.');
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
