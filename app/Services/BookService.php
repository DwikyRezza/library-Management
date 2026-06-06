<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function create(array $data): Book
    {
        return DB::transaction(function () use ($data): Book {
            $book = Book::query()->create([
                ...Arr::only($data, [
                    'title',
                    'author',
                    'publisher',
                    'publication_year',
                    'isbn',
                    'category_id',
                    'description',
                ]),
                'slug' => $this->uniqueSlug($data['title']),
            ]);

            $this->addCopies($book, (int) $data['number_of_copies'], $data['shelf_location'] ?? null);

            return $book->refresh();
        });
    }

    public function update(Book $book, array $data): Book
    {
        return DB::transaction(function () use ($book, $data): Book {
            $book->fill(Arr::only($data, [
                'title',
                'author',
                'publisher',
                'publication_year',
                'isbn',
                'category_id',
                'description',
            ]));

            if ($book->isDirty('title')) {
                $book->slug = $this->uniqueSlug($data['title'], $book);
            }

            $book->save();

            return $book->refresh();
        });
    }

    public function addCopies(Book $book, int $count, ?string $shelfLocation = null): void
    {
        DB::transaction(function () use ($book, $count, $shelfLocation): void {
            $book = Book::query()->whereKey($book->id)->lockForUpdate()->firstOrFail();
            $currentCount = $book->copies()->withTrashed()->count();

            for ($index = 1; $index <= $count; $index++) {
                $copyNumber = $currentCount + $index;

                BookCopy::query()->create([
                    'book_id' => $book->id,
                    'copy_code' => sprintf('LIB-%04d-%03d', $book->id, $copyNumber),
                    'shelf_location' => $shelfLocation,
                    'status' => BookCopy::STATUS_AVAILABLE,
                ]);
            }

            $book->refreshCopyCounters();
        });
    }

    public function updateCopyStatus(BookCopy $copy, string $status, ?string $conditionNote = null): void
    {
        DB::transaction(function () use ($copy, $status, $conditionNote): void {
            $copy = BookCopy::query()->whereKey($copy->id)->lockForUpdate()->firstOrFail();

            if ($copy->activeTransaction()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Copy status cannot be changed while it is actively borrowed.',
                ]);
            }

            $copy->forceFill([
                'status' => $status,
                'condition_note' => $conditionNote,
            ])->save();

            $copy->book->refreshCopyCounters();
        });
    }

    public function delete(Book $book): void
    {
        DB::transaction(function () use ($book): void {
            $book = Book::query()->whereKey($book->id)->lockForUpdate()->firstOrFail();
            $hasActiveTransactions = $book->copies()
                ->whereHas('transactions', fn ($query) => $query->active())
                ->exists();

            if ($hasActiveTransactions) {
                throw ValidationException::withMessages([
                    'book' => 'Book cannot be deleted while one of its copies is actively borrowed.',
                ]);
            }

            $book->delete();
        });
    }

    private function uniqueSlug(string $title, ?Book $ignore = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 2;

        while (Book::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
