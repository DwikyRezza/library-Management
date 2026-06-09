<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookHighlightRequest;
use App\Models\BookHighlight;
use App\Services\DigitalLoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MemberReaderHighlightController extends Controller
{
    public function store(
        StoreBookHighlightRequest $request,
        DigitalLoanService $digitalLoanService
    ): JsonResponse {
        $highlight = $digitalLoanService->createHighlight(
            $request->user('member'),
            $request->validated(),
        );

        return response()->json([
            'data' => $highlight->only([
                'id',
                'page_number',
                'highlighted_text',
                'color',
                'serialized_range',
            ]),
        ], 201);
    }

    public function destroy(
        Request $request,
        BookHighlight $bookHighlight,
        DigitalLoanService $digitalLoanService
    ): Response {
        $digitalLoanService->deleteHighlight(
            $request->user('member'),
            $bookHighlight,
        );

        return response()->noContent();
    }
}
