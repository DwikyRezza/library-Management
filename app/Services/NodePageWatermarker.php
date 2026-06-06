<?php

namespace App\Services;

use App\Contracts\PageWatermarker;
use App\Models\DigitalBookAsset;
use App\Models\Member;
use App\Models\ReadingSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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
        $disk = Storage::disk('local');

        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException('Halaman buku digital tidak ditemukan.');
        }

        if ($disk->exists($targetPath)) {
            return $targetPath;
        }

        return Cache::lock("watermark:{$session->uuid}:{$page}", 30)->block(10, function () use (

            $member,
            $session,
            $sourcePath,
            $targetPath,
            $disk
        ): string {
            if ($disk->exists($targetPath)) {
                return $targetPath;
            }

            $process = new Process([
                (string) config('services.digital_reader.node_binary', 'node'),
                base_path('scripts/watermark-page.mjs'),
                $disk->path($sourcePath),
                $disk->path($targetPath),
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

            return $targetPath;
        });
    }
}
