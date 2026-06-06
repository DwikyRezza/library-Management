<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookCategoryController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\CirculationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberRegistrationController;
use App\Http\Controllers\PublicBookController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicBookController::class, 'home'])->name('home');
Route::get('/books/search', [PublicBookController::class, 'search'])->name('books.search');
Route::get('/member/register', [MemberRegistrationController::class, 'create'])->name('member.register');
Route::post('/member/register', [MemberRegistrationController::class, 'store'])->name('member.register.store');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'staff.active'])
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::resource('categories', BookCategoryController::class)->except('show');
        Route::resource('books', BookController::class);
        Route::post('/books/{book}/copies', [BookCopyController::class, 'store'])->name('books.copies.store');
        Route::patch('/book-copies/{bookCopy}', [BookCopyController::class, 'update'])->name('book-copies.update');

        Route::get('/members/pending', [MemberController::class, 'pending'])->name('members.pending');
        Route::post('/members/{member}/approve', [MemberController::class, 'approve'])->name('members.approve');
        Route::post('/members/{member}/reject', [MemberController::class, 'reject'])->name('members.reject');
        Route::resource('members', MemberController::class)->except(['create', 'store']);

        Route::get('/circulation', [CirculationController::class, 'index'])->name('circulation.index');
        Route::post('/circulation/issue', [CirculationController::class, 'issue'])->name('circulation.issue');
        Route::post('/circulation/return', [CirculationController::class, 'return'])->name('circulation.return');

        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/books/export', [ReportController::class, 'exportBooks'])->name('reports.books.export');
        Route::get('/reports/transactions/export', [ReportController::class, 'exportTransactions'])->name('reports.transactions.export');
    });
