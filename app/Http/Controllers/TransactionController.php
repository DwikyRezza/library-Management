<?php

namespace App\Http\Controllers;

use App\Models\BorrowTransaction;
use App\Services\CirculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request, CirculationService $circulation): View
    {
        $circulation->syncOverdueStatuses();

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:borrowed,returned,overdue'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $transactions = BorrowTransaction::query()
            ->with(['bookCopy.book', 'member', 'issuedBy', 'returnedBy'])
            ->search($filters['q'] ?? null)
            ->status($filters['status'] ?? null)
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('issued_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('issued_at', '<=', $date))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }

    public function show(BorrowTransaction $transaction): View
    {
        return view('admin.transactions.show', [
            'transaction' => $transaction->load([
                'bookCopy.book.category',
                'member.memberCategory',
                'member.branch',
                'issuedBy',
                'returnedBy',
            ]),
        ]);
    }
}
