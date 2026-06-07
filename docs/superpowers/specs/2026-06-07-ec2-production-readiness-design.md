# EC2 Production Readiness Design

## Goal

Prepare the LibraFlow repository for a public deployment on one Ubuntu EC2
instance without managing the user's AWS account, DNS, TLS certificate, or
production credentials.

## Deployment Architecture

One EC2 instance runs:

- Nginx as the public web server;
- PHP-FPM for Laravel;
- MySQL for application data;
- a Supervisor-managed Laravel queue worker;
- Node.js for PDF rendering and page watermarking.

Uploaded PDFs and generated page images remain on the private local Laravel
disk under `storage/app/private`. The operator is responsible for attaching
durable storage and backing up both MySQL and private files.

## Repository Deliverables

The repository will include:

- a production environment template without secrets;
- an Nginx site template with Laravel routing, private-file protection,
  security headers, health-check access, and a 64 MB request limit;
- a PHP-FPM override template with matching upload limits;
- a Supervisor worker template with queue timeouts aligned to PDF rendering;
- a deploy script that installs production dependencies, builds assets,
  migrates, caches Laravel, and restarts the queue;
- an interactive command for creating the first administrator without loading
  dummy seed data or using a default password;
- a pre-deploy check that validates required environment values, writable
  directories, required commands and PHP extensions, queue timeout ordering,
  and Laravel production caches;
- automated tests for repository-level production invariants;
- updated deployment documentation for applying the templates on EC2.

## Runtime Configuration

The production environment template uses:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- HTTPS-ready secure session cookies, which the operator may temporarily
  disable only while serving plain HTTP by IP;
- MySQL, database-backed cache, sessions, and queue;
- `DB_QUEUE_RETRY_AFTER=720`, longer than the 660-second worker timeout;
- daily logs at warning level;
- explicit PDF renderer timeout values.

No password, application key, AWS credential, token, domain, or fixed public IP
is committed.

The first administrator is created with `php artisan app:create-admin`. The
command validates unique identity fields and requires a confirmed password of
at least 12 characters with mixed case, numbers, and symbols.

## Health Checks

Laravel's existing `/up` route remains the process-level liveness check. A new
`/health/ready` route checks database connectivity and returns:

- HTTP 200 with `{"status":"ready"}` when Laravel and MySQL are usable;
- HTTP 503 with `{"status":"unavailable"}` when the database check fails.

The response exposes no exception text or credentials.

## Deployment Safety

The deploy script:

- exits on the first failed command;
- refuses to run unless `APP_ENV=production` and `APP_DEBUG=false`;
- uses `composer install --no-dev` and `npm ci`;
- runs tests before migration;
- enables maintenance mode before dependencies are changed;
- always attempts to bring the application back up when the script exits;
- does not seed production data, alter credentials, pull Git changes, or
  perform destructive migration commands.

The pre-deploy check is read-only except for framework cache commands that
Laravel normally requires during deployment.

## Testing

Feature tests cover readiness responses with an available and unavailable
database. Repository tests verify that templates contain required production
settings and that queue retry timing exceeds job execution timing.

Final verification includes:

- `php artisan test`;
- `npm run build`;
- Composer validation and platform checks;
- Laravel production cache generation;
- actual Node PDF rendering and watermarking;
- shell syntax checks for deployment scripts when Bash is available.

## Operator Responsibilities

The operator still must:

- provision and patch EC2;
- assign a stable public address;
- configure Security Groups;
- create the MySQL database and restricted database user;
- create the real `.env`;
- install Nginx, PHP-FPM, MySQL, Node.js, Composer, and Supervisor;
- configure TLS when a domain or IP certificate is available;
- schedule and test backups;
- configure monitoring, alerts, and log retention.
