<?php

namespace App\Console\Commands;

use App\Services\DigitalLoanService;
use Illuminate\Console\Command;

class ExpireDigitalLoans extends Command
{
    protected $signature = 'digital-loans:expire';

    protected $description = 'Return digital loans that have reached their due date';

    public function handle(DigitalLoanService $digitalLoanService): int
    {
        $count = $digitalLoanService->expireDueLoans();

        $this->info("{$count} pinjaman digital kedaluwarsa telah dikembalikan.");

        return self::SUCCESS;
    }
}
