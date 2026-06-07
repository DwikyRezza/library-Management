<?php

namespace App\Console\Commands;

use App\Services\ProductionReadinessChecker;
use Illuminate\Console\Command;

class CheckProductionReadiness extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Validate production configuration and required runtime services';

    public function handle(ProductionReadinessChecker $checker): int
    {
        $errors = $checker->errors();

        if ($errors !== []) {
            $this->components->error('Production readiness checks failed.');

            foreach ($errors as $error) {
                $this->line(" - {$error}");
            }

            return self::FAILURE;
        }

        $this->components->info('Production readiness checks passed.');

        return self::SUCCESS;
    }
}
