<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CirculationService
{
    public function issue(array $data, User $issuedBy): BorrowTransaction
    {
        try {
            return DB::transaction(function () use ($data, $issuedBy): BorrowTransaction {
                $copy = BookCopy::query()
                    ->with('book')
                    ->where('copy_code', $data['book_copy_code'])
                    ->lockForUpdate()
                    ->first();

                if (! $copy) {
                    throw ValidationException::withMessages(['book_copy_code' => 'Book copy was not found.']);
                }

                $member = Member::query()
                    ->with('memberCategory')
                    ->where('member_code', $data['member_lookup'])
                    ->orWhere('roll_number', $data['member_lookup'])
                    ->lockForUpdate()
                    ->first();

                if (! $member) {
                    throw ValidationException::withMessages(['member_lookup' => 'Member was not found.']);
                }

                $this->validateIssue($copy, $member);

                $issuedAt = now();
                $transaction = BorrowTransaction::query()->create([
                    'transaction_code' => $this->nextTransactionCode(),
                    'book_copy_id' => $copy->id,
                    'member_id' => $member->id,
                    'issued_by' => $issuedBy->id,
                    'issued_at' => $issuedAt,
                    'due_at' => $issuedAt->copy()->addDays($member->memberCategory->loan_days),
                    'status' => BorrowTransaction::STATUS_BORROWED,
                    'notes' => $data['notes'] ?? null,
                ]);

                $copyUpdated = BookCopy::query()
                    ->whereKey($copy->id)
                    ->where('status', BookCopy::STATUS_AVAILABLE)
                    ->update(['status' => BookCopy::STATUS_BORROWED]);

                $memberUpdated = Member::query()
                    ->whereKey($member->id)
                    ->where('books_borrowed_count', '<', $member->memberCategory->max_books)
                    ->increment('books_borrowed_count');

                $updated = Book::query()
                    ->whereKey($copy->book_id)
                    ->where('available_copies', '>', 0)
                    ->decrement('available_copies');

                if ($copyUpdated === 0 || $memberUpdated === 0 || $updated === 0) {
                    throw ValidationException::withMessages([
                        'book_copy_code' => 'Circulation state changed unexpectedly. Please retry.',
                    ]);
                }

                return $transaction->load(['bookCopy.book', 'member']);
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'book_copy_code' => 'This copy already has an active loan or circulation changed concurrently.',
            ]);
        }
    }

    public function return(array $data, User $returnedBy): BorrowTransaction
    {
        return DB::transaction(function () use ($data, $returnedBy): BorrowTransaction {
            $copy = BookCopy::query()
                ->where('copy_code', $data['book_copy_code'])
                ->lockForUpdate()
                ->first();

            if (! $copy) {
                throw ValidationException::withMessages(['book_copy_code' => 'Book copy was not found.']);
            }

            $transaction = BorrowTransaction::query()
                ->where('book_copy_id', $copy->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                throw ValidationException::withMessages(['book_copy_code' => 'This copy does not have an active transaction.']);
            }

            $member = Member::query()->whereKey($transaction->member_id)->lockForUpdate()->firstOrFail();
            $book = Book::query()->whereKey($copy->book_id)->lockForUpdate()->firstOrFail();

            if ($member->books_borrowed_count <= 0) {
                throw ValidationException::withMessages([
                    'book_copy_code' => 'Member borrowing counter is inconsistent.',
                ]);
            }

            if ($book->available_copies >= $book->total_copies) {
                throw ValidationException::withMessages([
                    'book_copy_code' => 'Book availability counter is inconsistent.',
                ]);
            }

            $transaction->forceFill([
                'returned_by' => $returnedBy->id,
                'returned_at' => now(),
                'status' => BorrowTransaction::STATUS_RETURNED,
                'notes' => $data['notes'] ?? $transaction->notes,
            ])->save();

            $copy->forceFill(['status' => BookCopy::STATUS_AVAILABLE])->save();

            $memberUpdated = Member::query()
                ->whereKey($member->id)
                ->where('books_borrowed_count', '>', 0)
                ->decrement('books_borrowed_count');

            $bookUpdated = Book::query()
                ->whereKey($book->id)
                ->whereColumn('available_copies', '<', 'total_copies')
                ->increment('available_copies');

            if ($memberUpdated === 0 || $bookUpdated === 0) {
                throw ValidationException::withMessages([
                    'book_copy_code' => 'Circulation counters changed unexpectedly. Please retry.',
                ]);
            }

            return $transaction->refresh()->load(['bookCopy.book', 'member']);
        });
    }

    public function syncOverdueStatuses(): int
    {
        return BorrowTransaction::query()
            ->where('status', BorrowTransaction::STATUS_BORROWED)
            ->whereNull('returned_at')
            ->where('due_at', '<', now())
            ->update(['status' => BorrowTransaction::STATUS_OVERDUE]);
    }

    private function validateIssue(BookCopy $copy, Member $member): void
    {
        if (! $member->approved) {
            throw ValidationException::withMessages(['member_lookup' => 'Member is still pending approval.']);
        }

        if ($member->rejected) {
            throw ValidationException::withMessages(['member_lookup' => 'Rejected members cannot borrow books.']);
        }

        if (! $copy->isAvailable()) {
            throw ValidationException::withMessages(['book_copy_code' => 'Book copy is not available.']);
        }

        if ($copy->activeTransaction()->exists()) {
            throw ValidationException::withMessages(['book_copy_code' => 'Book copy already has an active transaction.']);
        }

        if ($member->books_borrowed_count >= $member->memberCategory->max_books) {
            throw ValidationException::withMessages(['member_lookup' => 'Member has reached the borrowing limit.']);
        }
    }

    private function nextTransactionCode(): string
    {
        $date = now()->format('Ymd');

        do {
            $code = 'TRX-'.$date.'-'.Str::upper(Str::substr((string) Str::ulid(), -10));
        } while (BorrowTransaction::query()->where('transaction_code', $code)->exists());

        return $code;
    }
}
