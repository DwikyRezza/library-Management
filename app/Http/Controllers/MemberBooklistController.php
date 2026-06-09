<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Booklist;
use App\Services\DigitalLoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberBooklistController extends Controller
{
    public function index(DigitalLoanService $digitalLoanService): View
    {
        $member = Auth::guard('member')->user();
        $digitalLoanService->syncExpiredForMember($member);

        $books = Book::query()
            ->with(['category', 'digitalAsset'])
            ->whereHas(
                'booklistEntries',
                fn (Builder $query): Builder => $query->where('member_id', $member->id),
            )
            ->withExists([
                'activeDigitalLoans as has_active_digital_loan' => fn (Builder $query): Builder => $query
                    ->where('member_id', $member->id),
                'booklistEntries as is_in_booklist' => fn (Builder $query): Builder => $query
                    ->where('member_id', $member->id),
            ])
            ->orderBy('title')
            ->paginate(12);

        return view('member.booklist.index', compact('books'));
    }

    public function store(Book $book): RedirectResponse
    {
        Booklist::query()->firstOrCreate([
            'member_id' => Auth::guard('member')->id(),
            'book_id' => $book->id,
        ]);

        return back()->with('success', 'Buku ditambahkan ke Booklist.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        Booklist::query()
            ->where('member_id', Auth::guard('member')->id())
            ->where('book_id', $book->id)
            ->delete();

        return back()->with('success', 'Buku dihapus dari Booklist.');
    }
}
