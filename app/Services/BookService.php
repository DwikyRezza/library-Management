<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BookService
{
    public function __construct(private readonly DigitalBookService $digitalBookService) {}

    public function create(array $data, ?UploadedFile $coverImage = null, ?UploadedFile $pdf = null): Book
    {
        return DB::transaction(function () use ($data, $coverImage): Book {
            $slug = $this->uniqueSlug($data['title']);

            $coverPath = $coverImage
                ? $this->uploadCover($coverImage, $slug)
                : null;

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
                'slug' => $slug,
                'cover_image' => $coverPath,
            ]);

            $this->addCopies($book, (int) $data['number_of_copies'], $data['shelf_location'] ?? null);

            return $book->refresh();
        }, attempts: 1);

        // Upload PDF outside the DB transaction (S3 I/O should not block the transaction)
        // We re-fetch the book to attach the digital asset after commit.
        // The pdf upload is handled in the controller after create() returns.
    }

    public function update(Book $book, array $data, ?UploadedFile $coverImage = null, ?UploadedFile $pdf = null): Book
    {
        return DB::transaction(function () use ($book, $data, $coverImage): Book {
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

            if ($coverImage) {
                // Delete old cover if it exists
                if ($book->cover_image) {
                    Storage::disk('s3')->delete($book->cover_image);
                }
                $book->cover_image = $this->uploadCover($coverImage, $book->slug);
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

            // Delete cover image from S3
            if ($book->cover_image) {
                Storage::disk('s3')->delete($book->cover_image);
            }

            $book->delete();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function uploadCover(UploadedFile $file, string $slug): string
    {
        $ext = $file->getClientOriginalExtension();
        $path = "book-covers/{$slug}.{$ext}";

        $stored = Storage::disk('s3')->put($path, $file->get(), 'public');

        if ($stored === false) {
            throw new RuntimeException('Gagal menyimpan cover buku. Periksa konfigurasi S3.');
        }

        return $path;
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
