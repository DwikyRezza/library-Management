<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_local_storage_has_no_automatic_file_route(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }

    public function test_authenticated_staff_can_view_reports_and_export_books(): void
    {
        $user = User::factory()->create();
        $category = BookCategory::factory()->create();
        Book::factory()->for($category, 'category')->create(['title' => 'Exported Book']);

        $this->actingAs($user)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Reports');

        $response = $this->actingAs($user)
            ->get('/admin/reports/books/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('Exported Book', $response->streamedContent());
    }

    public function test_transaction_list_can_filter_overdue_loans(): void
    {
        $user = User::factory()->create();
        $copy = BookCopy::factory()->create();
        $member = Member::factory()->create();
        BorrowTransaction::factory()->create([
            'transaction_code' => 'TRX-OVERDUE-001',
            'book_copy_id' => $copy->id,
            'member_id' => $member->id,
            'issued_by' => $user->id,
            'issued_at' => now()->subDays(20),
            'due_at' => now()->subDays(6),
            'returned_at' => null,
            'status' => BorrowTransaction::STATUS_BORROWED,
        ]);

        $this->actingAs($user)
            ->get('/admin/transactions?status=overdue')
            ->assertOk()
            ->assertSee('TRX-OVERDUE-001')
            ->assertSee('Overdue');
    }

    public function test_book_csv_export_neutralizes_spreadsheet_formulas(): void
    {
        $user = User::factory()->create();
        $category = BookCategory::factory()->create();
        Book::factory()->for($category, 'category')->create([
            'title' => '=HYPERLINK("https://example.test")',
            'author' => '+SUM(1,1)',
        ]);

        $response = $this->actingAs($user)->get('/admin/reports/books/export');
        $content = $response->streamedContent();

        $this->assertStringContainsString('\'=HYPERLINK', $content);
        $this->assertStringContainsString('\'+SUM', $content);
    }
}
