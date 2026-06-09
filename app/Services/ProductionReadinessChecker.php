<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
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

        if (config('session.secure') !== true) {
            $errors[] = 'SESSION_SECURE_COOKIE must be true.';
        }

        foreach ([storage_path(), base_path('bootstrap/cache')] as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                $errors[] = "{$path} must exist and be writable.";
            }
        }

        try {
            DB::select('select 1');
        } catch (Throwable) {
            $errors[] = 'The configured database connection is unavailable.';
        }

        return $errors;
    }
}
