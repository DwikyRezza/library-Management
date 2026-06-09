<?php

namespace App\Services;

use App\Models\Book;
use App\Models\DigitalLoan;
use App\Models\Member;
use App\Models\ReadingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReadingSessionService
{
    public function __construct(
        private readonly DigitalLoanService $digitalLoanService,
    ) {}

    public function start(Member $member, Book $book, Request $request): ReadingSession
    {
        $loan = $this->digitalLoanService->activeLoanForBook($member, $book);

        if (! $loan) {
            throw ValidationException::withMessages([
                'book' => 'Pinjam buku digital ini terlebih dahulu sebelum mulai membaca.',
            ]);
        }

        $asset = $book->digitalAsset;

        if (! $asset?->isReady()) {
            throw ValidationException::withMessages([
                'book' => 'Buku digital ini belum siap dibaca.',
            ]);
        }

        $initialPage = max(1, (int) $loan->last_read_page);

        if ($asset->page_count > 0) {
            $initialPage = min($initialPage, $asset->page_count);
        }

        return ReadingSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'member_id' => $member->id,
            'book_id' => $book->id,
            'digital_book_asset_id' => $asset->id,
            'started_at' => now(),
            'last_active_at' => now(),
            'last_page' => $initialPage,
            'max_page' => $initialPage,
            'duration_seconds' => 0,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);
    }

    public function heartbeat(
        ReadingSession $session,
        DigitalLoan $loan,
        int $page,
        bool $finish = false,
        ?int $totalPages = null
    ): ReadingSession {
        return DB::transaction(function () use ($session, $loan, $page, $finish, $totalPages): ReadingSession {
            $session = ReadingSession::query()
                ->with('digitalBookAsset')
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $session->digitalBookAsset) {
                throw ValidationException::withMessages([
                    'book' => 'Versi buku digital untuk sesi ini sudah tidak tersedia.',
                ]);
            }

            if ($totalPages !== null && $session->digitalBookAsset->page_count !== $totalPages) {
                $session->digitalBookAsset->forceFill(['page_count' => $totalPages])->save();
            }

            $pageCount = $totalPages ?? $session->digitalBookAsset->page_count;

            if ($page < 1 || $page > $pageCount) {
                throw ValidationException::withMessages([
                    'page' => 'Nomor halaman tidak valid.',
                ]);
            }

            $now = now();
            $elapsed = max(0, $now->timestamp - $session->last_active_at->timestamp);
            $elapsed = min($elapsed, (int) config('services.digital_reader.heartbeat_cap', 60));

            $session->forceFill([
                'last_page' => $page,
                'max_page' => max($session->max_page, $page),
                'duration_seconds' => $session->duration_seconds + $elapsed,
                'last_active_at' => $now,
                'ended_at' => $finish ? $now : $session->ended_at,
            ])->save();

            $this->digitalLoanService->recordLastReadPage($loan, $page);

            return $session->refresh();
        });
    }
}
