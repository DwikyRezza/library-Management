<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadingSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $sessions = ReadingSession::query()
            ->with(['member:id,member_code,first_name,last_name,email', 'book:id,title,author'])
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->whereHas('member', function (Builder $member) use ($term): void {
                        $member->where('member_code', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    })->orWhereHas('book', function (Builder $book) use ($term): void {
                        $book->where('title', 'like', "%{$term}%")
                            ->orWhere('author', 'like', "%{$term}%");
                    });
                });
            })
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('started_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('started_at', '<=', $to))
            ->latest('last_active_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reading-history.index', compact('sessions'));
    }

    public function show(ReadingSession $readingSession): View
    {
        $readingSession->load(['member.memberCategory', 'member.branch', 'book', 'digitalBookAsset']);

        return view('admin.reading-history.show', [
            'session' => $readingSession,
        ]);
    }
}
