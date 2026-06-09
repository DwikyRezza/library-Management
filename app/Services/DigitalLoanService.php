<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\BookHighlight;
use App\Models\DigitalLoan;
use App\Models\Member;
use App\Models\ReadingSession;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DigitalLoanService
{
    public const MAX_ACTIVE_LOANS = 3;

    public const LOAN_DAYS = 10;

    public const HIGHLIGHT_COLORS = [
        '#fef08a',
        '#bbf7d0',
        '#bfdbfe',
    ];

    public function borrow(Member $member, Book $book): DigitalLoan
    {
        $this->syncExpiredForMember($member);

        try {
            return DB::transaction(function () use ($member, $book): DigitalLoan {
                $member = Member::query()
                    ->whereKey($member->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $book = Book::query()
                    ->with('digitalAsset')
                    ->whereKey($book->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->validateBorrower($member);

                if (! $book->digitalAsset?->isReady()) {
                    throw ValidationException::withMessages([
                        'book' => 'Buku ini belum memiliki PDF yang siap dibaca.',
                    ]);
                }

                if ($member->digitalLoans()->active()->where('book_id', $book->id)->exists()) {
                    throw ValidationException::withMessages([
                        'book' => 'Buku ini sudah ada di daftar pinjaman digital Anda.',
                    ]);
                }

                if ($member->digitalLoans()->active()->count() >= self::MAX_ACTIVE_LOANS) {
                    throw ValidationException::withMessages([
                        'book' => 'Batas tiga pinjaman digital aktif sudah tercapai.',
                    ]);
                }

                $copy = BookCopy::query()
                    ->where('book_id', $book->id)
                    ->available()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if (! $copy || $book->available_copies <= 0) {
                    throw ValidationException::withMessages([
                        'book' => 'Semua copy buku sedang dipinjam.',
                    ]);
                }

                $borrowedAt = now();
                $loan = DigitalLoan::query()->create([
                    'member_id' => $member->id,
                    'book_id' => $book->id,
                    'book_copy_id' => $copy->id,
                    'borrowed_at' => $borrowedAt,
                    'due_at' => $borrowedAt->copy()->addDays(self::LOAN_DAYS),
                    'last_read_page' => 1,
                ]);

                $copyUpdated = BookCopy::query()
                    ->whereKey($copy->id)
                    ->where('status', BookCopy::STATUS_AVAILABLE)
                    ->update(['status' => BookCopy::STATUS_BORROWED]);

                $bookUpdated = Book::query()
                    ->whereKey($book->id)
                    ->where('available_copies', '>', 0)
                    ->decrement('available_copies');

                if ($copyUpdated === 0 || $bookUpdated === 0) {
                    throw ValidationException::withMessages([
                        'book' => 'Status ketersediaan berubah. Silakan coba lagi.',
                    ]);
                }

                return $loan->load(['book.category', 'book.digitalAsset', 'bookCopy', 'member']);
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'book' => 'Buku gagal dipinjam karena statusnya baru saja berubah.',
            ]);
        }
    }

    public function extend(DigitalLoan $loan): DigitalLoan
    {
        return DB::transaction(function () use ($loan): DigitalLoan {
            $loan = DigitalLoan::query()
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $loan->canExtend()) {
                throw ValidationException::withMessages([
                    'loan' => 'Perpanjangan hanya tersedia sekali dalam 24 jam terakhir masa pinjam.',
                ]);
            }

            $loan->forceFill([
                'extended_at' => now(),
                'due_at' => $loan->due_at->copy()->addDays(self::LOAN_DAYS),
            ])->save();

            return $loan->refresh();
        });
    }

    public function returnLoan(
        DigitalLoan $loan,
        string $reason = DigitalLoan::RETURN_MANUAL
    ): DigitalLoan {
        return DB::transaction(function () use ($loan, $reason): DigitalLoan {
            $loan = DigitalLoan::query()
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($loan->returned_at !== null) {
                return $loan;
            }

            $copy = BookCopy::query()
                ->whereKey($loan->book_copy_id)
                ->lockForUpdate()
                ->firstOrFail();
            $book = Book::query()
                ->whereKey($loan->book_id)
                ->lockForUpdate()
                ->firstOrFail();

            $loan->forceFill([
                'returned_at' => now(),
                'return_reason' => $reason,
            ])->save();

            $copyUpdated = BookCopy::query()
                ->whereKey($copy->id)
                ->where('status', BookCopy::STATUS_BORROWED)
                ->update(['status' => BookCopy::STATUS_AVAILABLE]);

            $bookUpdated = Book::query()
                ->whereKey($book->id)
                ->whereColumn('available_copies', '<', 'total_copies')
                ->increment('available_copies');

            if ($copyUpdated === 0 || $bookUpdated === 0) {
                throw ValidationException::withMessages([
                    'loan' => 'Status stok pinjaman tidak konsisten. Hubungi pustakawan.',
                ]);
            }

            $loan->member
                ->readingSessions()
                ->where('book_id', $loan->book_id)
                ->whereNull('ended_at')
                ->update([
                    'ended_at' => now(),
                    'last_active_at' => now(),
                ]);

            return $loan->refresh();
        });
    }

    public function expireDueLoans(): int
    {
        $loanIds = DigitalLoan::query()
            ->expired()
            ->orderBy('id')
            ->pluck('id');

        foreach ($loanIds as $loanId) {
            $this->returnLoan(
                DigitalLoan::query()->findOrFail($loanId),
                DigitalLoan::RETURN_EXPIRED,
            );
        }

        return $loanIds->count();
    }

    public function syncExpiredForMember(Member $member): int
    {
        $loanIds = $member->digitalLoans()
            ->expired()
            ->orderBy('id')
            ->pluck('digital_loans.id');

        foreach ($loanIds as $loanId) {
            $this->returnLoan(
                DigitalLoan::query()->findOrFail($loanId),
                DigitalLoan::RETURN_EXPIRED,
            );
        }

        return $loanIds->count();
    }

    public function activeLoanForBook(Member $member, Book $book): ?DigitalLoan
    {
        $this->syncExpiredForMember($member);

        return $member->digitalLoans()
            ->active()
            ->where('book_id', $book->id)
            ->first();
    }

    public function activeLoanForSession(Member $member, ReadingSession $session): ?DigitalLoan
    {
        $this->syncExpiredForMember($member);

        return $member->digitalLoans()
            ->active()
            ->where('book_id', $session->book_id)
            ->first();
    }

    public function recordLastReadPage(DigitalLoan $loan, int $page): DigitalLoan
    {
        $activeLoan = DigitalLoan::query()
            ->active()
            ->whereKey($loan->id)
            ->lockForUpdate()
            ->first();

        if (! $activeLoan) {
            throw (new ModelNotFoundException)->setModel(DigitalLoan::class, [$loan->id]);
        }

        $activeLoan->forceFill(['last_read_page' => $page])->save();

        return $activeLoan->refresh();
    }

    public function createHighlight(Member $member, array $data): BookHighlight
    {
        $loan = $this->ownedActiveLoan($member, (int) $data['digital_loan_id']);
        $pageCount = (int) $loan->book()->with('digitalAsset')->firstOrFail()->digitalAsset?->page_count;

        if ($pageCount > 0 && (int) $data['page_number'] > $pageCount) {
            throw ValidationException::withMessages([
                'page_number' => 'Nomor halaman stabilo tidak valid.',
            ]);
        }

        return $loan->highlights()->create([
            'page_number' => $data['page_number'],
            'highlighted_text' => $data['highlighted_text'],
            'color' => $data['color'],
            'serialized_range' => $data['serialized_range'],
        ]);
    }

    public function deleteHighlight(Member $member, BookHighlight $highlight): void
    {
        $this->syncExpiredForMember($member);

        $ownedHighlight = BookHighlight::query()
            ->whereKey($highlight->id)
            ->whereHas('digitalLoan', function ($query) use ($member): void {
                $query->where('member_id', $member->id)->active();
            })
            ->firstOrFail();

        $ownedHighlight->delete();
    }

    private function ownedActiveLoan(Member $member, int $loanId): DigitalLoan
    {
        $this->syncExpiredForMember($member);

        return $member->digitalLoans()
            ->with(['book.digitalAsset'])
            ->active()
            ->whereKey($loanId)
            ->firstOrFail();
    }

    private function validateBorrower(Member $member): void
    {
        if (! $member->approved || $member->rejected) {
            throw ValidationException::withMessages([
                'book' => 'Akun harus disetujui pustakawan sebelum dapat meminjam.',
            ]);
        }

        if ($member->isProfileIncomplete()) {
            throw ValidationException::withMessages([
                'book' => 'Lengkapi profil sebelum meminjam buku digital.',
            ]);
        }
    }
}
