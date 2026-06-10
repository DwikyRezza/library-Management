<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListBookAnnotationsRequest;
use App\Http\Requests\StoreBookAnnotationRequest;
use App\Models\BookAnnotation;
use App\Models\Member;
use App\Models\ReadingSession;
use App\Services\DigitalLoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MemberReaderAnnotationController extends Controller
{
    public function __construct(
        private readonly DigitalLoanService $digitalLoanService,
    ) {}

    public function index(
        ListBookAnnotationsRequest $request,
        ReadingSession $readingSession
    ): JsonResponse {
        $member = $this->ensureActiveSession($request, $readingSession);
        $start = max(1, $request->integer('start', 1));
        $end = max($start, $request->integer('end', 100000));

        $annotations = BookAnnotation::query()
            ->where('member_id', $member->id)
            ->where('book_id', $readingSession->book_id)
            ->whereBetween('page_number', [$start, $end])
            ->orderBy('page_number')
            ->get(['id', 'page_number', 'data', 'updated_at']);

        return response()->json([
            'data' => $annotations,
        ]);
    }

    public function store(
        StoreBookAnnotationRequest $request,
        ReadingSession $readingSession
    ): JsonResponse {
        $member = $this->ensureActiveSession($request, $readingSession);
        $readingSession->loadMissing('digitalBookAsset');
        $pageNumber = $request->integer('page_number');
        $pageCount = (int) $readingSession->digitalBookAsset?->page_count;

        if ($pageCount > 0 && $pageNumber > $pageCount) {
            throw ValidationException::withMessages([
                'page_number' => 'Nomor halaman anotasi tidak valid.',
            ]);
        }

        $annotation = BookAnnotation::query()->updateOrCreate(
            [
                'member_id' => $member->id,
                'book_id' => $readingSession->book_id,
                'page_number' => $pageNumber,
            ],
            [
                'data' => $request->validated('data'),
            ],
        );

        return response()->json([
            'data' => $annotation->only([
                'id',
                'page_number',
                'data',
                'updated_at',
            ]),
        ]);
    }

    private function ensureActiveSession(Request $request, ReadingSession $session): Member
    {
        $member = $request->user('member');

        abort_unless($member && $session->member_id === $member->id, 404);
        abort_unless(
            $this->digitalLoanService->activeLoanForSession($member, $session),
            404,
        );

        return $member;
    }
}
