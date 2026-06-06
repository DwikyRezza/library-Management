<?php

namespace App\Jobs;

use App\Contracts\PdfPageRenderer;
use App\Models\DigitalBookAsset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class RenderDigitalBook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 660;

    public function __construct(public int $assetId) {}

    public function handle(PdfPageRenderer $renderer): void
    {
        $asset = DigitalBookAsset::query()->findOrFail($this->assetId);

        try {
            $pageCount = $renderer->render($asset);

            $asset->forceFill([
                'pages_path' => "digital-books/{$asset->uuid}/pages",
                'page_count' => $pageCount,
                'status' => DigitalBookAsset::STATUS_READY,
                'last_error' => null,
                'rendered_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $asset->forceFill([
                'status' => DigitalBookAsset::STATUS_FAILED,
                'last_error' => Str::limit($exception->getMessage(), 4000, ''),
            ])->save();

            throw $exception;
        }
    }
}
