<?php

namespace Tests\Feature\LibraFlow;

use App\Services\ProductionReadinessChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_readiness_endpoint_reports_ready_when_database_is_available(): void
    {
        $response = $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'ready']);

        $this->assertStringContainsString(
            'no-store',
            $response->headers->get('cache-control', '')
        );
    }

    public function test_readiness_endpoint_hides_database_failure_details(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andThrow(new RuntimeException('password=secret host=database.internal'));

        $response = $this->getJson('/health/ready')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'unavailable']);

        $response->assertDontSee('secret');
        $response->assertDontSee('database.internal');
    }

    public function test_readiness_endpoint_is_stateless(): void
    {
        $route = Route::getRoutes()->getByName('health.ready');

        $this->assertNotNull($route);
        $this->assertNotContains('web', $route->gatherMiddleware());
    }

    public function test_production_checker_accepts_safe_runtime_configuration(): void
    {
        $this->app['env'] = 'production';

        config([
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.url' => 'https://203.0.113.10',
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'libraflow',
            'database.connections.mysql.username' => 'libraflow_user',
            'database.connections.mysql.password' => 'strong-password',
            'queue.default' => 'database',
            'session.secure' => true,
        ]);

        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andReturn([(object) ['1' => 1]]);

        $this->assertSame([], app(ProductionReadinessChecker::class)->errors());
    }

    public function test_production_checker_rejects_unsafe_runtime_configuration(): void
    {
        config([
            'app.debug' => true,
            'app.key' => '',
            'app.url' => 'https://YOUR_PUBLIC_HOST_OR_IP',
            'database.default' => 'sqlite',
            'database.connections.mysql.database' => '',
            'database.connections.mysql.username' => '',
            'database.connections.mysql.password' => '',
            'queue.default' => 'sync',
            'session.secure' => false,
        ]);

        $errors = app(ProductionReadinessChecker::class)->errors();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('APP_ENV', implode("\n", $errors));
        $this->assertStringContainsString('APP_DEBUG', implode("\n", $errors));
        $this->assertStringContainsString('APP_KEY', implode("\n", $errors));
        $this->assertStringContainsString('APP_URL', implode("\n", $errors));
        $this->assertStringContainsString('DB_PASSWORD', implode("\n", $errors));
    }

    public function test_production_checker_does_not_require_the_legacy_pdf_renderer(): void
    {
        $this->app['env'] = 'production';

        config([
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.url' => 'https://203.0.113.10',
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'libraflow',
            'database.connections.mysql.username' => 'libraflow_user',
            'database.connections.mysql.password' => 'strong-password',
            'queue.default' => 'database',
            'session.secure' => true,
            'services.digital_reader.node_binary' => 'definitely-missing-node-binary',
        ]);

        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andReturn([(object) ['1' => 1]]);

        $errors = app(ProductionReadinessChecker::class)->errors();

        $this->assertStringNotContainsString('PDF renderer', implode("\n", $errors));
        $this->assertStringNotContainsString('PDF_RENDER_NODE_BINARY', implode("\n", $errors));
    }

    public function test_repository_contains_safe_ec2_deployment_artifacts(): void
    {
        $environment = $this->readProjectFile('.env.production.example');
        $nginx = $this->readProjectFile('deploy/nginx/libraflow.conf');
        $php = $this->readProjectFile('deploy/php/99-libraflow.ini');
        $supervisor = $this->readProjectFile('deploy/supervisor/libraflow-worker.conf');
        $cron = $this->readProjectFile('deploy/cron/libraflow-scheduler');
        $predeploy = $this->readProjectFile('deploy/scripts/predeploy-check.sh');
        $deploy = $this->readProjectFile('deploy/scripts/deploy.sh');

        $this->assertStringContainsString('APP_ENV=production', $environment);
        $this->assertStringContainsString('APP_DEBUG=false', $environment);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $environment);
        $this->assertStringContainsString('DB_QUEUE_RETRY_AFTER=720', $environment);
        $this->assertStringContainsString("APP_KEY=\n", $environment);
        $this->assertStringContainsString("DB_PASSWORD=\n", $environment);

        $this->assertStringContainsString('client_max_body_size 128m;', $nginx);
        $this->assertStringContainsString('upload_max_filesize = 100M', $php);
        $this->assertStringContainsString('post_max_size = 128M', $php);
        $this->assertStringContainsString('root /var/www/html/public;', $nginx);
        $this->assertStringContainsString('php8.4-fpm.sock', $nginx);
        $this->assertStringContainsString('--timeout=660', $supervisor);
        $this->assertStringContainsString('stopwaitsecs=700', $supervisor);
        $this->assertStringContainsString('/usr/bin/php8.4 /var/www/html/artisan', $supervisor);
        $this->assertStringContainsString('php8.4 artisan schedule:run', $cron);
        $this->assertStringContainsString('cd /var/www/html', $cron);
        $this->assertStringContainsString('deploy/cron/libraflow-scheduler', $predeploy);
        $this->assertStringContainsString('APP_DIR="${APP_DIR:-/var/www/html}"', $predeploy);
        $this->assertStringContainsString('app:production-check', $predeploy);
        $this->assertStringNotContainsString('scripts/render-pdf.mjs', $predeploy);
        $this->assertStringNotContainsString('scripts/watermark-page.mjs', $predeploy);
        $testCommand = 'APP_ENV=testing APP_MAINTENANCE_DRIVER=cache APP_MAINTENANCE_STORE=array php artisan test';
        $this->assertStringContainsString($testCommand, $deploy);
        $this->assertStringContainsString('APP_DIR="${APP_DIR:-/var/www/html}"', $deploy);
        $this->assertStringContainsString('rm -rf -- public/build', $deploy);
        $this->assertStringContainsString('php artisan migrate --force', $deploy);
        $this->assertTrue(
            strpos($deploy, 'php artisan down') < strpos($deploy, 'composer install'),
            'Maintenance mode must start before dependencies are changed.'
        );
        $this->assertTrue(
            strpos($deploy, 'php artisan config:clear') < strpos($deploy, $testCommand),
            'Cached production configuration must be cleared before tests run.'
        );

        $deploymentFiles = strtolower($predeploy."\n".$deploy);
        $this->assertStringNotContainsString('git reset --hard', $deploymentFiles);
        $this->assertStringNotContainsString('git clean', $deploymentFiles);
        $this->assertStringNotContainsString('migrate:fresh', $deploymentFiles);
        $this->assertStringNotContainsString('db:wipe', $deploymentFiles);
    }

    private function readProjectFile(string $path): string
    {
        $absolutePath = base_path($path);

        $this->assertFileExists($absolutePath);

        return (string) file_get_contents($absolutePath);
    }
}
