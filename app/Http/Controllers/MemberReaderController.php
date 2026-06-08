<?php

namespace App\Http\Controllers;

use App\Contracts\PageWatermarker;
use App\Http\Requests\ReadingHeartbeatRequest;
use App\Models\Book;
use App\Models\ReadingSession;
use App\Services\ReadingSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberReaderController extends Controller
{
    public function open(Book $book, Request $request, ReadingSessionService $readingSessionService): RedirectResponse
    {
        $book->load('digitalAsset');
        $session = $readingSessionService->start(Auth::guard('member')->user(), $book, $request);

        return redirect()->route('member.reader.show', $session);
    }

    public function show(ReadingSession $readingSession): View
    {
        $this->ensureOwner($readingSession);
        $readingSession->load(['book', 'digitalBookAsset']);

        abort_unless($readingSession->digitalBookAsset?->isReady(), 404);

        return view('member.reader.show', [
            'session' => $readingSession,
            'book' => $readingSession->book,
            'asset' => $readingSession->digitalBookAsset,
        ]);
    }

    public function page(
        ReadingSession $readingSession,
        int $page,
        PageWatermarker $watermarker
    ): StreamedResponse {
        $this->ensureOwner($readingSession);
        $readingSession->loadMissing(['member', 'digitalBookAsset']);
        $asset = $readingSession->digitalBookAsset;

        abort_unless($asset?->isReady() && $page >= 1 && $page <= $asset->page_count, 404);

        try {
            $path = $watermarker->watermark($asset, $readingSession->member, $readingSession, $page);
        } catch (RuntimeException $exception) {
            Log::warning('Digital reader page could not be prepared.', [
                'asset_id' => $asset->id,
                'session_id' => $readingSession->id,
                'page' => $page,
                'message' => $exception->getMessage(),
            ]);

            abort(404);
        }

        $disk = Storage::disk($this->digitalBookDiskName());
        $stream = $disk->readStream($path);

        abort_unless(is_resource($stream), 404);

        $response = response()->stream(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="page.png"',
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
        $session = $readingSessionService->heartbeat($readingSession, $request->integer('page'));

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
        $session = $readingSessionService->heartbeat($readingSession, $request->integer('page'), true);

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

    private function digitalBookDiskName(): string
    {
        return (string) config('services.digital_reader.storage_disk', config('filesystems.default', 'local'));
    }
}
