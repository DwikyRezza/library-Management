<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDigitalBookRequest;
use App\Models\Book;
use App\Models\DigitalBookAsset;
use App\Services\DigitalBookService;
use Illuminate\Http\RedirectResponse;

class DigitalBookController extends Controller
{
    public function store(
        StoreDigitalBookRequest $request,
        Book $book,
        DigitalBookService $digitalBookService
    ): RedirectResponse {
        $digitalBookService->replace($book, $request->file('pdf'), $request->user());

        return redirect()
            ->route('admin.books.show', $book)
            ->with('success', 'PDF tersimpan privat dan siap dibaca.');
    }

    public function destroy(
        Book $book,
        DigitalBookAsset $digitalBookAsset,
        DigitalBookService $digitalBookService
    ): RedirectResponse {
        abort_unless($digitalBookAsset->book_id === $book->id, 404);

        $digitalBookService->delete($digitalBookAsset);

        return redirect()
            ->route('admin.books.show', $book)
            ->with('success', 'Buku digital berhasil dihapus.');
    }
}
