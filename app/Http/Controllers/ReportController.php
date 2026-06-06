<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Services\CirculationService;
use App\Services\ReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportService $reports, CirculationService $circulation): View
    {
        $circulation->syncOverdueStatuses();

        return view('admin.reports.index', [
            'topBooks' => $reports->topBorrowedBooks(),
            'activeMembers' => $reports->mostActiveMembers(),
            'borrowedBooks' => $reports->borrowedBooks(),
            'overdueBooks' => $reports->overdueBooks(),
            'monthlyTransactionCount' => $reports->monthlyTransactionCount(),
        ]);
    }

    public function exportBooks(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Title', 'Author', 'ISBN', 'Category', 'Total Copies', 'Available Copies']);

            Book::query()
                ->with('category')
                ->orderBy('title')
                ->chunkById(200, function ($books) use ($output): void {
                    foreach ($books as $book) {
                        fputcsv($output, [
                            $this->csvCell($book->title),
                            $this->csvCell($book->author),
                            $this->csvCell($book->isbn),
                            $this->csvCell($book->category->name),
                            $book->total_copies,
                            $book->available_copies,
                        ]);
                    }
                });

            fclose($output);
        }, 'libraflow-books-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportTransactions(CirculationService $circulation): StreamedResponse
    {
        $circulation->syncOverdueStatuses();

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Transaction Code',
                'Book',
                'Copy Code',
                'Member',
                'Issued At',
                'Due At',
                'Returned At',
                'Status',
            ]);

            BorrowTransaction::query()
                ->with(['bookCopy.book', 'member'])
                ->orderBy('id')
                ->chunkById(200, function ($transactions) use ($output): void {
                    foreach ($transactions as $transaction) {
                        fputcsv($output, [
                            $this->csvCell($transaction->transaction_code),
                            $this->csvCell($transaction->bookCopy->book->title),
                            $this->csvCell($transaction->bookCopy->copy_code),
                            $this->csvCell($transaction->member->full_name),
                            $transaction->issued_at?->toDateTimeString(),
                            $transaction->due_at?->toDateTimeString(),
                            $transaction->returned_at?->toDateTimeString(),
                            $this->csvCell($transaction->display_status),
                        ]);
                    }
                });

            fclose($output);
        }, 'libraflow-transactions-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function csvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
