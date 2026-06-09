# Digital Loans, Booklist, and Member UX Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun pinjaman digital 10 hari berbasis stok copy, Booklist, halaman Borrowed, pembatasan reader, serta penyempurnaan navbar dan motion publik/member.

**Architecture:** `digital_loans` menyimpan transaksi digital terpisah tetapi mengunci `book_copies` yang sama dengan sirkulasi fisik. `DigitalLoanService` menjadi satu-satunya pemilik operasi borrow, extend, return, dan expire secara transaksional; controller member hanya melakukan otorisasi dan redirect. Katalog menerima state pinjaman/booklist dari query controller agar komponen kartu tidak membuat N+1 query.

**Tech Stack:** Laravel 12, MySQL/SQLite, Blade, Alpine.js, Tailwind CSS 4, Lucide, PHPUnit

---

### Task 1: Data Model Digital Loan dan Booklist

**Files:**
- Create: `database/migrations/2026_06_09_000001_create_digital_loans_and_booklists_tables.php`
- Create: `app/Models/DigitalLoan.php`
- Create: `app/Models/Booklist.php`
- Create: `database/factories/DigitalLoanFactory.php`
- Modify: `app/Models/Member.php`
- Modify: `app/Models/Book.php`
- Modify: `app/Models/BookCopy.php`
- Test: `tests/Feature/LibraFlow/DigitalLoanTest.php`

- [ ] **Step 1: Write failing relationship and schema tests**

Create tests that insert a digital loan with `borrowed_at`, `due_at`,
`extended_at`, `returned_at`, and `return_reason`, then assert the member, book,
and copy relationships. Add a unique Booklist pair assertion.

- [ ] **Step 2: Run the tests and verify RED**

```bash
php artisan test tests/Feature/LibraFlow/DigitalLoanTest.php
```

Expected: FAIL because `digital_loans`, `booklists`, and their models do not
exist.

- [ ] **Step 3: Add migration and models**

The migration creates:

```php
Schema::create('digital_loans', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('member_id')->constrained()->restrictOnDelete();
    $table->foreignId('book_id')->constrained()->restrictOnDelete();
    $table->foreignId('book_copy_id')->constrained()->restrictOnDelete();
    $table->timestamp('borrowed_at')->index();
    $table->timestamp('due_at')->index();
    $table->timestamp('extended_at')->nullable();
    $table->timestamp('returned_at')->nullable()->index();
    $table->string('return_reason')->nullable();
    $table->timestamps();
    $table->index(['member_id', 'returned_at']);
    $table->index(['book_copy_id', 'returned_at']);
});

Schema::create('booklists', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('member_id')->constrained()->cascadeOnDelete();
    $table->foreignId('book_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['member_id', 'book_id']);
});
```

`DigitalLoan` exposes `active`, `expired`, `canExtend`, and `remainingSeconds`.
Models receive `digitalLoans`, `activeDigitalLoans`, and `booklistEntries`
relationships.

- [ ] **Step 4: Run the focused tests**

Expected: PASS.

### Task 2: Transactional Borrow, Extend, Return, and Expire

**Files:**
- Create: `app/Services/DigitalLoanService.php`
- Create: `app/Http/Controllers/MemberDigitalLoanController.php`
- Create: `app/Console/Commands/ExpireDigitalLoans.php`
- Modify: `routes/web.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/LibraFlow/DigitalLoanTest.php`

- [ ] **Step 1: Write failing borrow tests**

Cover:

```php
$this->actingAs($member, 'member')
    ->post(route('member.digital-loans.store', $book))
    ->assertRedirect(route('member.borrowed.index'));

$this->assertSame(0, $book->fresh()->available_copies);
$this->assertSame(BookCopy::STATUS_BORROWED, $copy->fresh()->status);
$this->assertSame(10, now()->diffInDays(DigitalLoan::first()->due_at));
```

Also test unapproved members, missing PDF, no available copy, duplicate borrow,
and the separate maximum of three active loans.

- [ ] **Step 2: Run focused tests and verify RED**

Expected: route/controller/service missing.

- [ ] **Step 3: Implement borrowing**

`DigitalLoanService::borrow(Member $member, Book $book)`:

- starts a database transaction;
- locks member and book;
- rejects unapproved/rejected/incomplete members;
- rejects missing ready PDF, duplicate active loan, or three active loans;
- selects the first available copy with `lockForUpdate`;
- creates a loan with `borrowed_at = now()` and `due_at = now()->addDays(10)`;
- conditionally changes the copy to borrowed and decrements availability;
- rolls back if either guarded update affects zero rows.

- [ ] **Step 4: Write failing extend/return/expire tests**

Test extension only at `due_at - 24 hours`, exactly once, adding 10 days from
the old due date. Test manual return and command expiration restore the copy,
increment availability, set the reason, and close active reading sessions.

- [ ] **Step 5: Implement lifecycle methods and scheduler**

Add:

```php
public function extend(DigitalLoan $loan): DigitalLoan;
public function return(DigitalLoan $loan, string $reason = DigitalLoan::RETURN_MANUAL): DigitalLoan;
public function expireDueLoans(): int;
public function syncExpiredForMember(Member $member): int;
```

Register:

```php
Schedule::command('digital-loans:expire')->everyMinute()->withoutOverlapping();
```

- [ ] **Step 6: Run focused tests**

Expected: all DigitalLoan tests PASS.

### Task 3: Reader Requires an Active Digital Loan

**Files:**
- Modify: `app/Services/ReadingSessionService.php`
- Modify: `app/Http/Controllers/MemberReaderController.php`
- Modify: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] **Step 1: Write failing reader authorization tests**

Replace pending-member direct-read expectations with:

- approved member without a loan is redirected with an error;
- approved member with an active loan can create/open a reading session;
- expired/returned loans cannot open the reader or PDF endpoint;
- returning a loan closes the current reading session.

- [ ] **Step 2: Run DigitalReader tests and verify RED**

Expected: current reader still allows ready PDF without a loan.

- [ ] **Step 3: Enforce active loan access**

Before creating or serving a reading session, synchronize expired loans and
require an active loan belonging to the authenticated member for the same book.
Return 404 for stale reader sessions and a validation redirect for direct open.

- [ ] **Step 4: Run DigitalReader and DigitalLoan tests**

Expected: PASS.

### Task 4: Booklist API and Page

**Files:**
- Create: `app/Http/Controllers/MemberBooklistController.php`
- Create: `resources/views/member/booklist/index.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/PublicBookController.php`
- Test: `tests/Feature/LibraFlow/MemberLibraryTest.php`

- [ ] **Step 1: Write failing Booklist tests**

Test login requirement, idempotent add, remove, member isolation, unique pairs,
and page rendering with current Borrow/Read state.

- [ ] **Step 2: Run tests and verify RED**

Expected: routes and table behavior missing.

- [ ] **Step 3: Implement controller and routes**

Routes:

```php
Route::get('/member/booklist', ...)->name('member.booklist.index');
Route::post('/member/booklist/{book}', ...)->name('member.booklist.store');
Route::delete('/member/booklist/{book}', ...)->name('member.booklist.destroy');
```

`store` uses `firstOrCreate`; `destroy` deletes only the authenticated member's
entry. The index eager-loads category, digital asset, active digital loan, and
booklist state.

- [ ] **Step 4: Run MemberLibrary tests**

Expected: Booklist tests PASS.

### Task 5: Borrowed Page and Member Actions

**Files:**
- Modify: `app/Http/Controllers/MemberDigitalLoanController.php`
- Create: `resources/views/member/borrowed/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/LibraFlow/MemberLibraryTest.php`

- [ ] **Step 1: Write failing Borrowed page tests**

Assert active and historical sections, Read, Return, remaining time, Extend only
inside the final 24 hours, and no actions on another member's loan.

- [ ] **Step 2: Run tests and verify RED**

- [ ] **Step 3: Implement page and actions**

Routes:

```php
Route::get('/member/borrowed', ...)->name('member.borrowed.index');
Route::post('/member/borrowed/{digitalLoan}/extend', ...)->name('member.borrowed.extend');
Route::delete('/member/borrowed/{digitalLoan}', ...)->name('member.borrowed.return');
```

The controller scopes every loan to `Auth::guard('member')->id()`, syncs expired
loans before queries, and delegates all mutations to `DigitalLoanService`.

- [ ] **Step 4: Run MemberLibrary and DigitalLoan tests**

Expected: PASS.

### Task 6: Catalog Actions, Description Modal, and Logged-In Home CTA

**Files:**
- Modify: `app/Http/Controllers/PublicBookController.php`
- Rewrite: `resources/views/components/book-reader-action.blade.php`
- Create: `resources/views/components/book-description-modal.blade.php`
- Modify: `resources/views/public/home.blade.php`
- Modify: `resources/views/public/book-search.blade.php`
- Test: `tests/Feature/LibraFlow/MemberLibraryTest.php`
- Test: `tests/Feature/LibraFlow/AuthAndPublicTest.php`

- [ ] **Step 1: Write failing UI state tests**

Assert:

- guest sees Description and Borrow but Borrow targets login;
- member without loan sees Borrow;
- active borrower sees Read and not Borrow;
- Booklist add/remove state;
- unavailable copies disable Borrow;
- logged-in home does not show `Register as member` and shows Catalog, Booklist,
  Borrowed counts.

- [ ] **Step 2: Run tests and verify RED**

- [ ] **Step 3: Add efficient query state**

For authenticated member queries, add `withExists` aliases:

```php
activeDigitalLoans as has_active_digital_loan
booklistEntries as is_in_booklist
```

Expire the member's overdue loans before building the query. Avoid per-card
database queries.

- [ ] **Step 4: Build reusable actions and modal**

The action component renders Description, Booklist, Borrow, Read, and disabled
availability states. Description modal displays title, author, metadata, and
the full synopsis or a clear fallback.

- [ ] **Step 5: Run public/member tests**

Expected: PASS.

### Task 7: Navbar Icons and Motion System

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/js/app.js`
- Modify: `resources/css/app.css`
- Test: `tests/Feature/LibraFlow/MemberLibraryTest.php`

- [ ] **Step 1: Write failing navbar/motion markup tests**

Assert no visible `Light`/`Dark` labels or member first name, and assert Lucide
`Sun`, `Moon`, `UserRound`, Booklist/Borrowed menu links, page transition class,
interactive transition class, and reduced-motion CSS.

- [ ] **Step 2: Run tests and verify RED**

- [ ] **Step 3: Implement icon navigation**

Use icon-only theme/profile controls with `aria-label`, `title`, and stable
40px dimensions. Add Booklist and Borrowed menu entries.

- [ ] **Step 4: Implement restrained motion**

Add:

```css
html { scroll-behavior: smooth; }
.page-enter { animation: page-enter 280ms ease-out both; }
.interactive { transition: color 180ms, background-color 180ms, border-color 180ms, transform 180ms, box-shadow 180ms; }
.interactive:hover { transform: translateY(-1px); }
@media (prefers-reduced-motion: reduce) { /* disable animation and smooth scroll */ }
```

Apply stable hover classes to buttons, navigation, cards, and modal surfaces.

- [ ] **Step 5: Run tests and build**

```bash
php artisan test tests/Feature/LibraFlow/MemberLibraryTest.php
npm run build
```

Expected: PASS.

### Task 8: Deployment and Full Verification

**Files:**
- Create: `deploy/cron/libraflow-scheduler`
- Modify: `deploy/scripts/predeploy-check.sh`
- Modify: `penjelasan.md`
- Modify: `tests/Feature/LibraFlow/ProductionReadinessTest.php`

- [ ] **Step 1: Write failing deployment artifact test**

Assert the cron artifact calls:

```cron
* * * * * www-data cd /var/www/html && php8.4 artisan schedule:run >> /dev/null 2>&1
```

and predeploy verifies `deploy/cron/libraflow-scheduler`.

- [ ] **Step 2: Run ProductionReadiness tests and verify RED**

- [ ] **Step 3: Add cron artifact and deployment documentation**

Document installation through `/etc/cron.d/libraflow-scheduler`, ownership,
permissions `0644`, cron restart, migration, and smoke tests for Borrow,
Booklist, Borrowed, Extend, Return, and automatic expiration.

- [ ] **Step 4: Run formatter, build, and full suite**

```bash
php vendor/bin/pint --dirty --test
npm run build
php artisan test
git diff --check
```

Expected: no formatting/build/test/whitespace failures.
