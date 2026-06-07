<?php

namespace Tests\Feature\LibraFlow;

use App\Jobs\RenderDigitalBook;
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

    public function test_database_queue_retry_window_exceeds_pdf_job_timeout(): void
    {
        $job = new RenderDigitalBook(1);

        $this->assertGreaterThan(
            $job->timeout,
            config('queue.connections.database.retry_after')
        );
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
            'queue.connections.database.retry_after' => 720,
            'session.secure' => true,
            'services.digital_reader.job_timeout' => 660,
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

    public function test_production_checker_reports_a_missing_node_binary_without_crashing(): void
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
            'queue.connections.database.retry_after' => 720,
            'session.secure' => true,
            'services.digital_reader.job_timeout' => 660,
            'services.digital_reader.node_binary' => 'definitely-missing-node-binary',
        ]);

        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andReturn([(object) ['1' => 1]]);

        $errors = app(ProductionReadinessChecker::class)->errors();

        $this->assertStringContainsString(
            'Node.js',
            implode("\n", $errors)
        );
    }

    public function test_production_checker_requires_a_supported_node_version(): void
    {
        $checker = app(ProductionReadinessChecker::class);

        $this->assertFalse($checker->supportsNodeVersion('v20.19.0'));
        $this->assertFalse($checker->supportsNodeVersion('not-a-version'));
        $this->assertTrue($checker->supportsNodeVersion('v22.13.0'));
        $this->assertTrue($checker->supportsNodeVersion('v24.0.0'));
    }

    public function test_repository_contains_safe_ec2_deployment_artifacts(): void
    {
        $environment = $this->readProjectFile('.env.production.example');
        $nginx = $this->readProjectFile('deploy/nginx/libraflow.conf');
        $php = $this->readProjectFile('deploy/php/99-libraflow.ini');
        $supervisor = $this->readProjectFile('deploy/supervisor/libraflow-worker.conf');
        $predeploy = $this->readProjectFile('deploy/scripts/predeploy-check.sh');
        $deploy = $this->readProjectFile('deploy/scripts/deploy.sh');

        $this->assertStringContainsString('APP_ENV=production', $environment);
        $this->assertStringContainsString('APP_DEBUG=false', $environment);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $environment);
        $this->assertStringContainsString('DB_QUEUE_RETRY_AFTER=720', $environment);
        $this->assertStringContainsString("APP_KEY=\n", $environment);
        $this->assertStringContainsString("DB_PASSWORD=\n", $environment);

        $this->assertStringContainsString('client_max_body_size 64m;', $nginx);
        $this->assertStringContainsString('upload_max_filesize = 50M', $php);
        $this->assertStringContainsString('post_max_size = 64M', $php);
        $this->assertStringContainsString('--timeout=660', $supervisor);
        $this->assertStringContainsString('stopwaitsecs=700', $supervisor);
        $this->assertStringContainsString('app:production-check', $predeploy);
        $testCommand = 'APP_ENV=testing APP_MAINTENANCE_DRIVER=cache APP_MAINTENANCE_STORE=array php artisan test';
        $this->assertStringContainsString($testCommand, $deploy);
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
