# Immersive Reader Highlights Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan resume halaman terakhir dan stabilo teks persisten pada single-page PDF reader.

**Architecture:** `DigitalLoanService` menjadi pemilik aturan posisi baca dan stabilo. Controller tetap tipis, Form Request menangani bentuk payload, dan PDF.js `TextLayer` menyediakan seleksi teks di atas canvas dengan koordinat stabilo relatif terhadap halaman.

**Tech Stack:** Laravel 12, Eloquent, Blade, Vite 7, PDF.js 6, vanilla JavaScript, Tailwind CSS 4.

---

### Task 1: Persisted Reader Data

**Files:**
- Create: `database/migrations/2026_06_09_000003_add_last_read_page_to_digital_loans_table.php`
- Create: `database/migrations/2026_06_09_000004_create_book_highlights_table.php`
- Create: `app/Models/BookHighlight.php`
- Modify: `app/Models/DigitalLoan.php`
- Test: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] **Step 1: Write failing model and migration tests**

```php
$loan->forceFill(['last_read_page' => 7])->save();
$highlight = $loan->highlights()->create([
    'page_number' => 7,
    'highlighted_text' => 'Selected text',
    'color' => '#fef08a',
    'serialized_range' => ['version' => 1, 'rects' => []],
]);

$this->assertSame(7, $loan->fresh()->last_read_page);
$this->assertSame($loan->id, $highlight->digital_loan_id);
```

- [ ] **Step 2: Run the focused test and confirm missing schema/relations**

Run: `php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php`

Expected: FAIL because `last_read_page`, `book_highlights`, and relationships do not exist.

- [ ] **Step 3: Add migrations and relationships**

Create an unsigned integer `last_read_page` with default `1`. Create `book_highlights` with a cascading foreign key to `digital_loans`, page/text/color/range fields, timestamps, and a page index.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php`

Expected: PASS for data model cases.

### Task 2: Resume And Highlight Services

**Files:**
- Create: `app/Http/Requests/StoreBookHighlightRequest.php`
- Create: `app/Http/Controllers/MemberReaderHighlightController.php`
- Modify: `app/Services/DigitalLoanService.php`
- Modify: `app/Services/ReadingSessionService.php`
- Modify: `app/Http/Controllers/MemberReaderController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] **Step 1: Write failing API and heartbeat tests**

```php
$this->actingAs($member, 'member')
    ->postJson(route('member.reader.heartbeat', $session), ['page' => 4])
    ->assertOk();

$this->assertSame(4, $loan->fresh()->last_read_page);

$this->actingAs($member, 'member')
    ->postJson(route('member.reader.highlights.store'), [
        'digital_loan_id' => $loan->id,
        'page_number' => 4,
        'highlighted_text' => 'Selected text',
        'color' => '#fef08a',
        'serialized_range' => $range,
    ])
    ->assertCreated();
```

- [ ] **Step 2: Run focused tests and verify route/service failures**

Run: `php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php`

Expected: FAIL because highlight routes and resume service behavior are absent.

- [ ] **Step 3: Implement service-owned rules**

Add methods to find an owned active loan, save the last page, create a validated highlight, and delete an owned highlight. Start reading sessions at the loan's saved page and update the loan from heartbeat.

- [ ] **Step 4: Add thin endpoints**

Register:

```php
Route::post('/member/reader/highlight', [MemberReaderHighlightController::class, 'store'])
    ->name('member.reader.highlights.store');
Route::delete('/member/reader/highlight/{bookHighlight}', [MemberReaderHighlightController::class, 'destroy'])
    ->name('member.reader.highlights.destroy');
```

- [ ] **Step 5: Run focused tests**

Run: `php artisan test tests/Feature/LibraFlow/DigitalReaderTest.php`

Expected: PASS including cross-member authorization cases.

### Task 3: Resume Labels

**Files:**
- Modify: `app/Http/Controllers/PublicBookController.php`
- Modify: `app/Http/Controllers/MemberBooklistController.php`
- Modify: `resources/views/components/book-reader-action.blade.php`
- Modify: `resources/views/member/borrowed/index.blade.php`
- Test: `tests/Feature/LibraFlow/MemberLibraryTest.php`

- [ ] **Step 1: Write failing label tests**

Assert that home, search, booklist, and borrowed views contain `Lanjutkan Membaca (Hal. 6)` for an active loan whose `last_read_page` is `6`.

- [ ] **Step 2: Run the focused test**

Run: `php artisan test tests/Feature/LibraFlow/MemberLibraryTest.php`

Expected: FAIL because only boolean active-loan state is loaded.

- [ ] **Step 3: Load and render resume state**

Use constrained `withMax` on `activeDigitalLoans.last_read_page`, then derive:

```php
$readLabel = $lastReadPage > 1
    ? "Lanjutkan Membaca (Hal. {$lastReadPage})"
    : 'Read';
```

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/LibraFlow/MemberLibraryTest.php`

Expected: PASS.

### Task 4: Single-Page Text And Highlight Layers

**Files:**
- Create: `resources/css/reader.css`
- Modify: `resources/views/member/reader/show.blade.php`
- Modify: `resources/js/reader.js`
- Test: `tests/Feature/LibraFlow/DigitalReaderTest.php`

- [ ] **Step 1: Assert reader markup and endpoint data**

Assert the view exposes `readerTextLayer`, `readerHighlightLayer`, the store URL, delete URL template, and serialized existing highlights.

- [ ] **Step 2: Build the layered page surface**

Place canvas, highlight layer, and text layer inside a stable relative container. Add a fixed color popover with three accessible swatch buttons.

- [ ] **Step 3: Render PDF.js text**

Import:

```js
import { getDocument, GlobalWorkerOptions, TextLayer } from 'pdfjs-dist';
```

Render `TextLayer` with `pdfPage.streamTextContent(...)`, preserving the module worker port.

- [ ] **Step 4: Serialize selection and persist highlights**

Store span anchors plus normalized client rectangles. POST JSON asynchronously, redraw saved rectangles, and DELETE the matching highlight when a collapsed click lands inside one.

- [ ] **Step 5: Run frontend build**

Run: `npm.cmd run build`

Expected: Vite build succeeds and emits the reader and worker assets.

### Task 5: Full Verification

**Files:**
- Modify only files requiring formatter output.

- [ ] **Step 1: Format PHP**

Run: `vendor/bin/pint`

Expected: PASS.

- [ ] **Step 2: Run all Laravel tests**

Run: `php artisan test`

Expected: all tests pass with zero failures.

- [ ] **Step 3: Build production frontend**

Run: `npm.cmd run build`

Expected: exit code `0`.

- [ ] **Step 4: Inspect repository changes**

Run: `git diff --check` and `git status --short`

Expected: no whitespace errors and only intentional files changed.
