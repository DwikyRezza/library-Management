<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

class ProductionReadinessChecker
{
    /**
     * @return list<string>
     */
    public function errors(): array
    {
        $errors = [];
        $url = (string) config('app.url');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $jobTimeout = (int) config('services.digital_reader.job_timeout', 660);
        $retryAfter = (int) config('queue.connections.database.retry_after', 0);

        if (! app()->environment('production')) {
            $errors[] = 'APP_ENV must be production.';
        }

        if ((bool) config('app.debug')) {
            $errors[] = 'APP_DEBUG must be false.';
        }

        if (blank(config('app.key'))) {
            $errors[] = 'APP_KEY must be generated.';
        }

        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            $errors[] = 'APP_URL must use HTTPS.';
        }

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $errors[] = 'APP_URL must use the public host or IP.';
        }

        if (str_contains(strtolower($url), 'your_public_host_or_ip')) {
            $errors[] = 'APP_URL placeholder must be replaced.';
        }

        if (config('database.default') !== 'mysql') {
            $errors[] = 'DB_CONNECTION must be mysql.';
        }

        foreach (['database', 'username', 'password'] as $databaseSetting) {
            if (blank(config("database.connections.mysql.{$databaseSetting}"))) {
                $errors[] = 'DB_'.strtoupper($databaseSetting).' must be configured.';
            }
        }

        if (config('queue.default') !== 'database') {
            $errors[] = 'QUEUE_CONNECTION must be database.';
        }

        if ($retryAfter <= $jobTimeout) {
            $errors[] = "DB_QUEUE_RETRY_AFTER must exceed the {$jobTimeout}-second PDF job timeout.";
        }

        if (config('session.secure') !== true) {
            $errors[] = 'SESSION_SECURE_COOKIE must be true.';
        }

        foreach ([storage_path(), base_path('bootstrap/cache')] as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                $errors[] = "{$path} must exist and be writable.";
            }
        }

        foreach (['scripts/render-pdf.mjs', 'scripts/watermark-page.mjs'] as $script) {
            if (! is_file(base_path($script))) {
                $errors[] = "{$script} is missing.";
            }
        }

        $nodeProcess = new Process([
            (string) config('services.digital_reader.node_binary', 'node'),
            '--version',
        ]);
        $nodeProcess->setTimeout(10);
        $nodeProcess->run();

        if (! $nodeProcess->isSuccessful()) {
            $errors[] = 'Node.js is unavailable through PDF_RENDER_NODE_BINARY.';
        } elseif (! $this->supportsNodeVersion($nodeProcess->getOutput())) {
            $errors[] = 'Node.js 22.13.0 or newer is required by the PDF renderer.';
        }

        try {
            DB::select('select 1');
        } catch (Throwable) {
            $errors[] = 'The configured database connection is unavailable.';
        }

        return $errors;
    }

    public function supportsNodeVersion(string $version): bool
    {
        $normalized = ltrim(trim($version), 'vV');

        if (! preg_match('/^\d+\.\d+\.\d+(?:[-+].*)?$/', $normalized)) {
            return false;
        }

        return version_compare($normalized, '22.13.0', '>=');
    }
}
