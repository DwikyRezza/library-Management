<?php

namespace App\Services;

use App\Contracts\PdfPageRenderer;
use App\Models\DigitalBookAsset;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class NodePdfPageRenderer implements PdfPageRenderer
{
    public function render(DigitalBookAsset $asset): int
    {
        $inputPath = Storage::disk('local')->path($asset->original_path);
        $outputPath = Storage::disk('local')->path("digital-books/{$asset->uuid}/pages");

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

        return $pageCount;
    }
}
