<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookCopyRequest;
use App\Http\Requests\UpdateBookCopyStatusRequest;
use App\Models\Book;
use App\Models\BookCopy;
use App\Services\BookService;
use Illuminate\Http\RedirectResponse;

class BookCopyController extends Controller
{
    public function store(StoreBookCopyRequest $request, Book $book, BookService $bookService): RedirectResponse
    {
        $data = $request->validated();
        $bookService->addCopies($book, (int) $data['number_of_copies'], $data['shelf_location'] ?? null);

        return redirect()->route('admin.books.show', $book)->with('success', 'Book copies added.');
    }

    public function update(
        UpdateBookCopyStatusRequest $request,
        BookCopy $bookCopy,
        BookService $bookService,
    ): RedirectResponse {
        $bookService->updateCopyStatus(
            $bookCopy,
            $request->validated('status'),
            $request->validated('condition_note'),
        );

        return redirect()->route('admin.books.show', $bookCopy->book_id)->with('success', 'Copy status updated.');
    }
}
