<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadingHeartbeatRequest;
use App\Models\Book;
use App\Models\DigitalLoan;
use App\Models\ReadingSession;
use App\Services\DigitalBookService;
use App\Services\DigitalLoanService;
use App\Services\ReadingSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberReaderController extends Controller
{
    public function __construct(
        private readonly DigitalLoanService $digitalLoanService,
    ) {}

    public function open(Book $book, Request $request, ReadingSessionService $readingSessionService): RedirectResponse
    {
        $book->load('digitalAsset');
        $session = $readingSessionService->start(Auth::guard('member')->user(), $book, $request);

        return redirect()->route('member.reader.show', $session);
    }

    public function show(ReadingSession $readingSession): View
    {
        $loan = $this->ensureActiveLoan($readingSession);
        $readingSession->load(['book', 'digitalBookAsset']);

        abort_unless($readingSession->digitalBookAsset?->isReady(), 404);

        return view('member.reader.show', [
            'session' => $readingSession,
            'book' => $readingSession->book,
            'asset' => $readingSession->digitalBookAsset,
            'loan' => $loan,
            'initialPage' => max(1, (int) $readingSession->last_page),
            'highlights' => $loan->highlights()
                ->select([
                    'id',
                    'digital_loan_id',
                    'page_number',
                    'highlighted_text',
                    'color',
                    'serialized_range',
                ])
                ->orderBy('page_number')
                ->orderBy('id')
                ->get()
                ->map(fn ($highlight): array => $highlight->only([
                    'id',
                    'page_number',
                    'highlighted_text',
                    'color',
                    'serialized_range',
                ]))
                ->values(),
        ]);
    }

    public function document(
        ReadingSession $readingSession,
        DigitalBookService $digitalBookService
    ): StreamedResponse {
        $this->ensureActiveLoan($readingSession);
        $readingSession->loadMissing('digitalBookAsset');
        $asset = $readingSession->digitalBookAsset;

        abort_unless($asset?->isReady(), 404);

        $stream = $digitalBookService->readStream($asset);

        abort_unless(is_resource($stream), 404);

        $response = response()->stream(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) $asset->file_size,
            'Content-Disposition' => 'inline; filename="book.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function heartbeat(
        ReadingHeartbeatRequest $request,
        ReadingSession $readingSession,
        ReadingSessionService $readingSessionService
    ): JsonResponse {
        $loan = $this->ensureActiveLoan($readingSession);

        $session = $readingSessionService->heartbeat(
            $readingSession,
            $loan,
            $request->integer('page'),
            false,
            $request->filled('total_pages') ? $request->integer('total_pages') : null,
        );

        return response()->json([
            'lastPage' => $session->last_page,
            'maxPage' => $session->max_page,
            'durationSeconds' => $session->duration_seconds,
        ]);
    }

    public function finish(
        ReadingHeartbeatRequest $request,
        ReadingSession $readingSession,
        ReadingSessionService $readingSessionService
    ): JsonResponse {
        $loan = $this->ensureActiveLoan($readingSession);

        $session = $readingSessionService->heartbeat(
            $readingSession,
            $loan,
            $request->integer('page'),
            true,
            $request->filled('total_pages') ? $request->integer('total_pages') : null,
        );

        return response()->json([
            'lastPage' => $session->last_page,
            'durationSeconds' => $session->duration_seconds,
            'endedAt' => $session->ended_at?->toIso8601String(),
        ]);
    }

    private function ensureOwner(ReadingSession $session): void
    {
        abort_unless($session->member_id === Auth::guard('member')->id(), 404);
    }

    private function ensureActiveLoan(ReadingSession $session): DigitalLoan
    {
        $this->ensureOwner($session);

        $member = Auth::guard('member')->user();
        $loan = $this->digitalLoanService->activeLoanForSession($member, $session);

        abort_unless($loan, 404);

        return $loan;
    }
}
