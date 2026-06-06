<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;

class ReportService
{
    public function dashboardStats(): array
    {
        return [
            'books' => Book::query()->count(),
            'copies' => BookCopy::query()->count(),
            'available_copies' => BookCopy::query()->where('status', BookCopy::STATUS_AVAILABLE)->count(),
            'borrowed_copies' => BookCopy::query()->where('status', BookCopy::STATUS_BORROWED)->count(),
            'members' => Member::query()->count(),
            'pending_members' => Member::query()->approvalStatus(Member::STATUS_PENDING)->count(),
            'active_transactions' => BorrowTransaction::query()->active()->count(),
            'overdue_transactions' => BorrowTransaction::query()->overdue()->count(),
        ];
    }

    public function recentBorrowed(int $limit = 5): Collection
    {
        return BorrowTransaction::query()
            ->with(['bookCopy.book', 'member'])
            ->active()
            ->latest('issued_at')
            ->limit($limit)
            ->get();
    }

    public function recentReturned(int $limit = 5): Collection
    {
        return BorrowTransaction::query()
            ->with(['bookCopy.book', 'member'])
            ->where('status', BorrowTransaction::STATUS_RETURNED)
            ->latest('returned_at')
            ->limit($limit)
            ->get();
    }

    public function pendingMembers(int $limit = 5): Collection
    {
        return Member::query()
            ->with(['memberCategory', 'branch'])
            ->approvalStatus(Member::STATUS_PENDING)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function topBorrowedBooks(int $limit = 5): Collection
    {
        return Book::query()
            ->select('books.*')
            ->selectSub(function ($query): void {
                $query->from('borrow_transactions')
                    ->join('book_copies', 'book_copies.id', '=', 'borrow_transactions.book_copy_id')
                    ->whereColumn('book_copies.book_id', 'books.id')
                    ->selectRaw('COUNT(*)');
            }, 'borrow_count')
            ->orderByDesc('borrow_count')
            ->limit($limit)
            ->get();
    }

    public function mostActiveMembers(int $limit = 5): Collection
    {
        return Member::query()
            ->select('members.*')
            ->selectSub(function ($query): void {
                $query->from('borrow_transactions')
                    ->whereColumn('borrow_transactions.member_id', 'members.id')
                    ->selectRaw('COUNT(*)');
            }, 'transaction_count')
            ->orderByDesc('transaction_count')
            ->limit($limit)
            ->get();
    }

    public function borrowedBooks(int $limit = 10): Collection
    {
        return BorrowTransaction::query()
            ->with(['bookCopy.book', 'member'])
            ->active()
            ->latest('issued_at')
            ->limit($limit)
            ->get();
    }

    public function overdueBooks(int $limit = 10): Collection
    {
        return BorrowTransaction::query()
            ->with(['bookCopy.book', 'member'])
            ->overdue()
            ->oldest('due_at')
            ->limit($limit)
            ->get();
    }

    public function monthlyTransactionCount(): int
    {
        return BorrowTransaction::query()
            ->whereBetween('issued_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }
}
