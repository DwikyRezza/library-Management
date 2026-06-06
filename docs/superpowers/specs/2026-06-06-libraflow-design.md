# LibraFlow Design

## Product Boundary

LibraFlow is a server-rendered library management application for public
catalog discovery and authenticated librarian operations. The first release
covers catalog, member approval, circulation, transaction history, reporting,
CSV export, responsive navigation, and persistent light/dark appearance.

## Framework Version Decision

The requested Laravel 11 line reached the end of security support on March 12,
2026. On June 6, 2026 Composer also blocks every available Laravel 11 release
because of an active advisory. LibraFlow therefore uses Laravel 12, which keeps
the same PHP 8.2 baseline and streamlined application structure while remaining
within security support. Composer's insecure-package protection is not disabled.

## Considered Approaches

1. **Layered Laravel application (selected).** Form Requests validate input,
   controllers coordinate HTTP behavior, services own multi-model business
   rules, models own relationships/scopes, and Blade renders data. This matches
   the existing foundation and keeps circulation testable.
2. **Controller-oriented CRUD.** Faster initially, but circulation and approval
   rules would become duplicated and difficult to test.
3. **Package-heavy admin panel.** Provides CRUD quickly, but adds dependencies,
   limits the requested original UI, and obscures business behavior.

## Architecture

- Public controllers expose landing, catalog search, registration, and login.
- Authenticated `/admin` routes use Laravel's `auth` middleware and an active
  staff middleware.
- Resource controllers remain thin and delegate writes to `BookService`,
  `MemberService`, and `CirculationService`.
- Reports are assembled by `ReportService` using aggregate queries and eager
  loading.
- Reusable Blade components render alerts, badges, modals, form errors, empty
  states, and pagination-safe tables.

## Data Integrity

- Issue and return operations run inside database transactions.
- The selected member, copy, transaction, and affected counters are locked or
  updated conditionally before state changes.
- A partial unique index prevents two active transactions for one copy on
  SQLite. Service validation and copy status provide portable protection.
- Counters are never decremented below zero and are bounded by physical copy
  totals.
- Approval and rejection are idempotent only when the member is already in the
  requested final state. Opposite final states require a future reactivation
  or suspension workflow.

## User Experience

- Public pages use a bright indigo/emerald visual system with catalog cards.
- Admin pages use a responsive sidebar, compact top bar, statistic cards,
  filters, tables, badges, and confirmation modals.
- Alpine.js handles sidebar state, confirmation dialogs, submit disabling, and
  theme state. Theme preference is stored in `localStorage`.
- All important actions use POST/Redirect/GET and visible flash feedback.

## Security And Operations

- Authentication accepts email or username, rate limits attempts, regenerates
  sessions after login, and invalidates sessions on logout.
- Staff accounts must be active and have an allowed role.
- CSRF, Form Requests, escaped Blade output, route-model binding, fillable
  properties, and non-GET mutations are used throughout.
- SQLite is the development default. README documents database creation,
  production cache commands, writable directories, and MySQL configuration.

## Testing

Feature tests cover login, public search, registration, member decisions,
catalog management, issue/return rules, protected routes, reports, exports, and
counter invariants. The release gate is:

1. `php artisan migrate:fresh --seed`
2. `php artisan test`
3. `php artisan route:list`
4. `npm run build`
5. `vendor/bin/pint --test`
