<?php

namespace App\Jobs;

use App\Models\DigitalBookAsset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RenderDigitalBook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout;

    public function __construct(public int $assetId)
    {
        $this->timeout = (int) config('services.digital_reader.job_timeout', 660);
    }

    public function handle(): void
    {
        $asset = DigitalBookAsset::query()->find($this->assetId);

        if (! $asset) {
            return;
        }

        $asset->forceFill([
            'status' => DigitalBookAsset::STATUS_READY,
            'last_error' => null,
            'rendered_at' => now(),
        ])->save();
    }
}
