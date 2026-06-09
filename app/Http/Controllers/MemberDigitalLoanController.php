<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\DigitalLoan;
use App\Services\DigitalLoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberDigitalLoanController extends Controller
{
    public function index(DigitalLoanService $digitalLoanService): View
    {
        $member = Auth::guard('member')->user();
        $digitalLoanService->syncExpiredForMember($member);

        return view('member.borrowed.index', [
            'activeLoans' => $member->digitalLoans()
                ->with(['book.category', 'book.digitalAsset', 'bookCopy'])
                ->active()
                ->orderBy('due_at')
                ->get(),
            'loanHistory' => $member->digitalLoans()
                ->with(['book.category', 'book.digitalAsset', 'bookCopy'])
                ->whereNotNull('returned_at')
                ->latest('returned_at')
                ->paginate(10),
        ]);
    }

    public function store(Book $book, DigitalLoanService $digitalLoanService): RedirectResponse
    {
        $digitalLoanService->borrow(Auth::guard('member')->user(), $book);

        return redirect()
            ->route('member.borrowed.index')
            ->with('success', 'Buku digital berhasil dipinjam selama 10 hari.');
    }

    public function extend(DigitalLoan $digitalLoan, DigitalLoanService $digitalLoanService): RedirectResponse
    {
        $loan = $this->ownedLoan($digitalLoan);
        $digitalLoanService->extend($loan);

        return redirect()
            ->route('member.borrowed.index')
            ->with('success', 'Masa pinjam berhasil diperpanjang 10 hari.');
    }

    public function destroy(DigitalLoan $digitalLoan, DigitalLoanService $digitalLoanService): RedirectResponse
    {
        $loan = $this->ownedLoan($digitalLoan);
        $digitalLoanService->returnLoan($loan);

        return redirect()
            ->route('member.borrowed.index')
            ->with('success', 'Buku digital berhasil dikembalikan.');
    }

    private function ownedLoan(DigitalLoan $digitalLoan): DigitalLoan
    {
        abort_unless($digitalLoan->member_id === Auth::guard('member')->id(), 404);

        return $digitalLoan;
    }
}
