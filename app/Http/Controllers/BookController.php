<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookCopy;
use App\Services\BookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:book_categories,id'],
            'availability' => ['nullable', 'in:available,unavailable'],
        ]);

        $books = Book::query()
            ->with('category')
            ->search($filters['q'] ?? null)
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->when(($filters['availability'] ?? null) === 'available', fn ($query) => $query->where('available_copies', '>', 0))
            ->when(($filters['availability'] ?? null) === 'unavailable', fn ($query) => $query->where('available_copies', 0))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.books.index', [
            'books' => $books,
            'categories' => BookCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.books.create', [
            'categories' => BookCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBookRequest $request, BookService $bookService): RedirectResponse
    {
        $book = $bookService->create($request->validated());

        return redirect()->route('admin.books.show', $book)->with('success', 'Book and copies created.');
    }

    public function show(Book $book): View
    {
        $book->load([
            'category',
            'copies' => fn ($query) => $query->orderBy('copy_code'),
            'digitalAsset.uploader',
        ]);

        $recentTransactions = $book->transactions()
            ->with(['member', 'bookCopy'])
            ->latest('issued_at')
            ->limit(10)
            ->get();

        return view('admin.books.show', [
            'book' => $book,
            'recentTransactions' => $recentTransactions,
            'copyStatuses' => BookCopy::STATUSES,
        ]);
    }

    public function edit(Book $book): View
    {
        return view('admin.books.edit', [
            'book' => $book,
            'categories' => BookCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateBookRequest $request, Book $book, BookService $bookService): RedirectResponse
    {
        $bookService->update($book, $request->validated());

        return redirect()->route('admin.books.show', $book)->with('success', 'Book updated.');
    }

    public function destroy(Book $book, BookService $bookService): RedirectResponse
    {
        $bookService->delete($book);

        return redirect()->route('admin.books.index')->with('success', 'Book archived.');
    }
}
