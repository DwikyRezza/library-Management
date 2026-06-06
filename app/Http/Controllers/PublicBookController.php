<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicBookController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'featuredBooks' => Book::query()
                ->with(['category', 'digitalAsset'])
                ->orderByDesc('available_copies')
                ->latest()
                ->limit(6)
                ->get(),
            'bookCount' => Book::query()->count(),
            'categoryCount' => BookCategory::query()->count(),
            'availableCount' => Book::query()->sum('available_copies'),
        ]);
    }

    public function search(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:book_categories,id'],
        ]);

        $books = Book::query()
            ->with(['category', 'digitalAsset'])
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
}
