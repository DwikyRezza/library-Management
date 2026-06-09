<?php

namespace App\Console\Commands;

use App\Models\DigitalBookAsset;
use App\Services\DigitalBookService;
use Illuminate\Console\Command;
use Throwable;

class RepairDigitalBookStorage extends Command
{
    protected $signature = 'digital-books:repair-storage';

    protected $description = 'Locate legacy digital PDFs and copy them to the configured storage disk';

    public function handle(DigitalBookService $digitalBookService): int
    {
        $available = 0;
        $copied = 0;
        $missing = 0;
        $failed = 0;

        DigitalBookAsset::query()
            ->with('book:id,title')
            ->orderBy('id')
            ->eachById(function (DigitalBookAsset $asset) use (
                $digitalBookService,
                &$available,
                &$copied,
                &$missing,
                &$failed,
            ): void {
                $title = $asset->book?->title ?? "Aset #{$asset->id}";

                try {
                    $result = $digitalBookService->repairStorage($asset);
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error("[GAGAL] {$title}: {$exception->getMessage()}");

                    return;
                }

                if ($result['status'] === DigitalBookService::REPAIR_COPIED) {
                    $copied++;
                    $this->info(
                        "[DIPINDAHKAN] {$title}: {$result['source_disk']} -> {$result['target_disk']}"
                    );

                    return;
                }

                if ($result['status'] === DigitalBookService::REPAIR_AVAILABLE) {
                    $available++;
                    $this->line("[OK] {$title}: tersedia di {$result['target_disk']}");

                    return;
                }

                $missing++;
                $this->warn("[HILANG] {$title}: file PDF tidak ditemukan di storage.");
            });

        $this->newLine();
        $this->line(
            "Selesai: {$available} tersedia, {$copied} dipindahkan, {$missing} hilang, {$failed} gagal."
        );

        return $missing === 0 && $failed === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
