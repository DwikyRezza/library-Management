<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\Member;
use App\Services\DigitalLoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicBookController extends Controller
{
    public function cover(Book $book): StreamedResponse
    {
        abort_unless($book->cover_image, 404);

        $disk = Storage::disk((string) config('filesystems.default', 'local'));

        abort_unless($disk->exists($book->cover_image), 404);

        return $disk->response($book->cover_image, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function home(DigitalLoanService $digitalLoanService): View
    {
        $member = Auth::guard('member')->user();
        $this->syncExpiredLoans($member, $digitalLoanService);

        return view('public.home', [
            'featuredBooks' => $this->withMemberState(
                Book::query()->with(['category', 'digitalAsset']),
                $member,
            )
                ->orderByDesc('available_copies')
                ->latest()
                ->limit(6)
                ->get(),
            'bookCount' => Book::query()->count(),
            'categoryCount' => BookCategory::query()->count(),
            'availableCount' => Book::query()->sum('available_copies'),
            'booklistCount' => $member?->booklistEntries()->count() ?? 0,
            'digitalLoanCount' => $member?->digitalLoans()->active()->count() ?? 0,
        ]);
    }

    public function search(Request $request, DigitalLoanService $digitalLoanService): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:book_categories,id'],
        ]);

        $member = Auth::guard('member')->user();
        $this->syncExpiredLoans($member, $digitalLoanService);

        $books = $this->withMemberState(
            Book::query()->with(['category', 'digitalAsset']),
            $member,
        )
            ->search($filters['q'] ?? null)
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('public.book-search', [
            'books' => $books,
            'categories' => BookCategory::query()->orderBy('name')->get(),
        ]);
    }

    private function withMemberState(Builder $query, ?Member $member): Builder
    {
        if (! $member) {
            return $query;
        }

        return $query
            ->withExists([
                'activeDigitalLoans as has_active_digital_loan' => fn (Builder $loanQuery): Builder => $loanQuery
                    ->where('member_id', $member->id),
                'booklistEntries as is_in_booklist' => fn (Builder $booklistQuery): Builder => $booklistQuery
                    ->where('member_id', $member->id),
            ])
            ->withMax([
                'activeDigitalLoans as active_loan_last_read_page' => fn (Builder $loanQuery): Builder => $loanQuery
                    ->where('member_id', $member->id),
            ], 'last_read_page');
    }

    private function syncExpiredLoans(?Member $member, DigitalLoanService $digitalLoanService): void
    {
        if ($member) {
            $digitalLoanService->syncExpiredForMember($member);
        }
    }
}
