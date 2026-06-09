<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadingHeartbeatRequest;
use App\Models\Book;
use App\Models\ReadingSession;
use App\Services\DigitalLoanService;
use App\Services\ReadingSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        $this->ensureActiveLoan($readingSession);
        $readingSession->load(['book', 'digitalBookAsset']);

        abort_unless($readingSession->digitalBookAsset?->isReady(), 404);

        return view('member.reader.show', [
            'session' => $readingSession,
            'book' => $readingSession->book,
            'asset' => $readingSession->digitalBookAsset,
        ]);
    }

    public function document(ReadingSession $readingSession): StreamedResponse
    {
        $this->ensureActiveLoan($readingSession);
        $readingSession->loadMissing('digitalBookAsset');
        $asset = $readingSession->digitalBookAsset;

        abort_unless($asset?->isReady(), 404);

        $disk = Storage::disk($this->digitalBookDiskName());
        $stream = $disk->readStream($asset->original_path);

        abort_unless(is_resource($stream), 404);

        $response = response()->stream(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
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
        $this->ensureActiveLoan($readingSession);

        $session = $readingSessionService->heartbeat(
            $readingSession,
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
        $this->ensureActiveLoan($readingSession);

        $session = $readingSessionService->heartbeat(
            $readingSession,
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

    private function ensureActiveLoan(ReadingSession $session): void
    {
        $this->ensureOwner($session);

        $member = Auth::guard('member')->user();
        $this->digitalLoanService->syncExpiredForMember($member);

        abort_unless(
            $member->digitalLoans()
                ->active()
                ->where('book_id', $session->book_id)
                ->exists(),
            404,
        );
    }

    private function digitalBookDiskName(): string
    {
        return (string) config('services.digital_reader.storage_disk', config('filesystems.default', 'local'));
    }
}
