# LibraFlow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a complete, runnable LibraFlow application from the existing domain foundation.

**Architecture:** HTTP concerns remain in Form Requests and thin controllers;
multi-model writes remain in services; Eloquent models expose relationships,
scopes, and status accessors; Blade components provide a cohesive responsive
interface. SQLite transactions, conditional updates, row locks where supported,
and an active-copy unique constraint protect circulation state.

**Tech Stack:** PHP 8.2, Laravel 12, SQLite, Blade, Tailwind CSS 4, Alpine.js,
Vite, PHPUnit.

---

### Task 1: Lock The Behavioral Contract

**Files:**
- Modify: `tests/Feature/LibraFlow/AuthAndPublicTest.php`
- Modify: `tests/Feature/LibraFlow/CirculationTest.php`
- Create: `tests/Feature/LibraFlow/AdminManagementTest.php`
- Create: `tests/Feature/LibraFlow/ReportAndSecurityTest.php`

- [ ] Add tests for username login, inactive login rejection, guest redirects,
      catalog CRUD constraints, copy status rules, member deletion constraints,
      transaction filters, reports, CSV export, and bounded counters.
- [ ] Run `php artisan test --testsuite=Feature` and verify the new tests fail
      because routes/controllers/views are absent.

### Task 2: Complete Authentication And Routing

**Files:**
- Create: `app/Http/Controllers/AuthController.php`
- Create: `app/Http/Middleware/EnsureStaffIsActive.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`

- [ ] Implement manual email/username authentication with remember-me, session
      regeneration, login throttling, inactive-account rejection, and logout.
- [ ] Register middleware and named public/admin routes.
- [ ] Run focused auth tests and verify they pass.

### Task 3: Complete Public And Dashboard HTTP Layers

**Files:**
- Create: `app/Http/Controllers/PublicBookController.php`
- Create: `app/Http/Controllers/MemberRegistrationController.php`
- Create: `app/Http/Controllers/DashboardController.php`

- [ ] Implement paginated public search with category filtering and eager
      loading.
- [ ] Implement registration reference data and service-backed registration.
- [ ] Implement dashboard statistics, recent activity, and pending approvals.
- [ ] Run public feature tests and verify they pass.

### Task 4: Complete Catalog And Member Management

**Files:**
- Create: `app/Http/Controllers/BookCategoryController.php`
- Create: `app/Http/Controllers/BookController.php`
- Create: `app/Http/Controllers/BookCopyController.php`
- Create: `app/Http/Controllers/MemberController.php`
- Modify: `app/Services/BookService.php`
- Modify: `app/Services/MemberService.php`

- [ ] Implement filtered, paginated resource actions and validation redirects.
- [ ] Prevent category deletion while used, book/member deletion while active,
      and borrowed-copy status changes.
- [ ] Add copy creation and maintenance/lost updates without resetting existing
      copies.
- [ ] Run catalog/member tests and verify they pass.

### Task 5: Harden Circulation And Transactions

**Files:**
- Create: `app/Http/Controllers/CirculationController.php`
- Create: `app/Http/Controllers/TransactionController.php`
- Modify: `app/Services/CirculationService.php`
- Modify: `app/Models/BorrowTransaction.php`

- [ ] Lock and re-read mutable records inside transactions.
- [ ] Generate collision-resistant transaction codes and translate uniqueness
      conflicts into validation errors.
- [ ] Keep book/member counters bounded and synchronize overdue status.
- [ ] Implement paginated transaction search, status/date filters, and detail.
- [ ] Run circulation tests and verify they pass.

### Task 6: Complete Reports And CSV Exports

**Files:**
- Create: `app/Http/Controllers/ReportController.php`
- Modify: `app/Services/ReportService.php`

- [ ] Render top books, active members, active/overdue loans, and monthly count.
- [ ] Stream escaped UTF-8 CSV exports for books and transactions.
- [ ] Run report/export tests and verify they pass.

### Task 7: Build The Blade Product Interface

**Files:**
- Create: `resources/views/layouts/app.blade.php`
- Create: `resources/views/layouts/admin.blade.php`
- Create: `resources/views/components/*.blade.php`
- Create: `resources/views/public/*.blade.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/admin/**/*.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Delete: `resources/views/welcome.blade.php`

- [ ] Build public hero, search cards, registration, and login.
- [ ] Build admin navigation, dashboard, CRUD forms/tables, circulation,
      transactions, and reports.
- [ ] Add Alpine theme/sidebar/modal/submit behavior and accessible focus
      states.
- [ ] Run HTTP feature tests to catch missing views, routes, and variables.

### Task 8: Normalize Runtime, Seed Data, And Documentation

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `.env.example`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `README.md`

- [ ] Keep Laravel on a security-supported release; Laravel 11 is blocked by
      Composer advisories as of June 6, 2026, so use Laravel 12.
- [ ] Keep SQLite as the default without modifying `.env`.
- [ ] Verify deterministic seed data includes all requested statuses.
- [ ] Document install, SQLite setup, credentials, architecture, workflows,
      deployment, security, manual checks, and future improvements.

### Task 9: Release Gate And Self-Audit

**Files:**
- Modify only files implicated by verification failures.

- [ ] Run `php artisan migrate:fresh --seed`.
- [ ] Run `php artisan test`.
- [ ] Run `php artisan route:list`.
- [ ] Run `npm install` and `npm run build`.
- [ ] Run `vendor/bin/pint --test`.
- [ ] Inspect query eager loading, route/view references, authorization,
      double-submit behavior, and counter invariants.
- [ ] Record remaining risks using Problem, Impact, Root Cause, Fix, and
      Improvement with severity.
