<?php

namespace App\Http\Controllers;

use App\Http\Requests\IssueBookRequest;
use App\Http\Requests\ReturnBookRequest;
use App\Models\BorrowTransaction;
use App\Services\CirculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CirculationController extends Controller
{
    public function index(CirculationService $circulation): View
    {
        $circulation->syncOverdueStatuses();

        return view('admin.circulation.index', [
            'activeTransactions' => BorrowTransaction::query()
                ->with(['bookCopy.book', 'member'])
                ->active()
                ->latest('issued_at')
                ->paginate(10),
        ]);
    }

    public function issue(IssueBookRequest $request, CirculationService $circulation): RedirectResponse
    {
        $transaction = $circulation->issue($request->validated(), $request->user());

        return redirect()
            ->route('admin.circulation.index')
            ->with('success', "Book issued successfully ({$transaction->transaction_code}).");
    }

    public function return(ReturnBookRequest $request, CirculationService $circulation): RedirectResponse
    {
        $transaction = $circulation->return($request->validated(), $request->user());

        return redirect()
            ->route('admin.circulation.index')
            ->with('success', "Book returned successfully ({$transaction->transaction_code}).");
    }
}
