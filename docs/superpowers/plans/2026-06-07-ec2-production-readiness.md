# EC2 Production Readiness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the LibraFlow repository deployable as a secure public Laravel application on one Ubuntu EC2 instance.

**Architecture:** Laravel owns application readiness checks and production configuration validation. Versioned deployment templates configure Nginx, PHP-FPM, and Supervisor, while Bash scripts provide repeatable pre-deploy validation and deployment without embedding credentials.

**Tech Stack:** Laravel 12, PHP 8.2+, PHPUnit 11, MySQL, Nginx, PHP-FPM, Supervisor, Node.js, Vite, Bash

---

### Task 1: Production Readiness Tests

**Files:**
- Create: `tests/Feature/LibraFlow/ProductionReadinessTest.php`

- [x] **Step 1: Write failing endpoint tests**

Add tests asserting that `/health/ready` returns HTTP 200 for a working database
and HTTP 503 without exception details when `DB::select()` throws.

- [x] **Step 2: Write failing repository invariant tests**

Assert that:

- queue `retry_after` exceeds the PDF job timeout;
- `.env.production.example` has production-safe values;
- Nginx, PHP-FPM, and Supervisor templates contain matching upload and queue
  limits;
- deployment scripts exist and contain no destructive Git or migration
  commands.

- [x] **Step 3: Run the focused tests**

Run:

```bash
php artisan test tests/Feature/LibraFlow/ProductionReadinessTest.php
```

Expected: FAIL because the readiness endpoint and deployment artifacts do not
exist and the queue retry timeout is still 90 seconds.

### Task 2: Application Readiness

**Files:**
- Create: `app/Http/Controllers/HealthController.php`
- Create: `app/Services/ProductionReadinessChecker.php`
- Create: `app/Console/Commands/CheckProductionReadiness.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Modify: `config/queue.php`
- Modify: `config/services.php`

- [x] **Step 1: Add the readiness endpoint**

Implement an invokable controller that executes `select 1`, returns a
no-store JSON response, and converts database failures to HTTP 503 without
returning exception text.

- [x] **Step 2: Add the production checker**

Validate production environment, disabled debug mode, non-empty application
key, non-local URL, MySQL, secure session cookies, database queue usage, queue
retry timing, writable Laravel directories, Node availability, renderer
scripts, and database connectivity.

- [x] **Step 3: Add and register the Artisan command**

Expose the checker as:

```bash
php artisan app:production-check
```

Return exit code 0 only when every check passes.

- [x] **Step 4: Align queue timing**

Set the database queue's default `retry_after` to 720 seconds and expose the
PDF job timeout as `PDF_JOB_TIMEOUT`, defaulting to 660 seconds.

- [x] **Step 5: Run focused tests**

Run:

```bash
php artisan test tests/Feature/LibraFlow/ProductionReadinessTest.php
```

Expected: endpoint and application configuration tests pass; artifact tests
remain failing.

### Task 3: Versioned Production Templates

**Files:**
- Create: `.env.production.example`
- Create: `deploy/nginx/libraflow.conf`
- Create: `deploy/php/99-libraflow.ini`
- Create: `deploy/supervisor/libraflow-worker.conf`
- Create: `deploy/scripts/predeploy-check.sh`
- Create: `deploy/scripts/deploy.sh`
- Modify: `.env.example`

- [x] **Step 1: Add the environment template**

Use placeholders for `APP_KEY`, `APP_URL`, and database credentials. Configure
production mode, secure cookies, daily warning logs, MySQL, database-backed
cache/session/queue, and retry timing.

- [x] **Step 2: Add server templates**

Configure:

- Nginx `client_max_body_size 64m`, Laravel front-controller routing, private
  dotfile protection, and security headers;
- PHP `upload_max_filesize=50M` and `post_max_size=64M`;
- Supervisor queue timeout 660 seconds and stop wait 700 seconds.

- [x] **Step 3: Add pre-deploy validation**

Check required commands and PHP extensions, then execute:

```bash
php artisan app:production-check
```

- [x] **Step 4: Add repeatable deployment**

Install dependencies, build assets, run tests, run readiness validation, enter
maintenance mode, install production-only dependencies, migrate, cache the
application, restart the queue, and restore service through an exit trap.

- [x] **Step 5: Run focused tests**

Run:

```bash
php artisan test tests/Feature/LibraFlow/ProductionReadinessTest.php
```

Expected: PASS.

### Task 4: EC2 Documentation

**Files:**
- Create: `app/Console/Commands/CreateAdmin.php`
- Create: `tests/Feature/LibraFlow/CreateAdminCommandTest.php`
- Modify: `README.md`

- [x] **Step 1: Add a secure admin bootstrap command**

Create `app:create-admin` with hidden password prompts, uniqueness validation,
and a minimum 12-character mixed-case password containing numbers and symbols.

- [x] **Step 2: Update the production environment section**

Reference `.env.production.example`, `DB_QUEUE_RETRY_AFTER=720`, secure
sessions, and the absence of committed secrets.

- [x] **Step 3: Replace inline server configuration**

Document how to install the versioned templates from `deploy/` and how to run
the pre-deploy and deploy scripts.

- [x] **Step 4: Add public launch checklist**

Cover Security Groups, stable IP, HTTPS, production check, health endpoints,
backups, Supervisor status, upload verification, and monitoring.

### Task 5: Final Verification

**Files:**
- Verify all modified files

- [x] **Step 1: Run backend tests**

```bash
php artisan test
```

Expected: all tests pass.

- [x] **Step 2: Run frontend build**

```bash
npm.cmd run build
```

Expected: Vite production build succeeds.

- [x] **Step 3: Validate dependencies and Laravel caches**

```bash
composer validate --strict --no-interaction
composer check-platform-reqs --no-dev
php artisan optimize
php artisan optimize:clear
```

Expected: all commands exit successfully.

- [x] **Step 4: Test the real PDF runtime**

Render `tests/Fixtures/minimal.pdf`, watermark the generated page, verify both
PNG files, and remove the temporary audit directory.

- [x] **Step 5: Inspect the final diff**

Confirm no `.env`, credential, generated build output, or unrelated file was
added.
