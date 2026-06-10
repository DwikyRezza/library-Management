<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'author',
        'publisher',
        'publication_year',
        'isbn',
        'description',
        'category_id',
        'cover_image',
        'total_copies',
        'available_copies',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'total_copies' => 'integer',
        'available_copies' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Book $book): void {
            if (blank($book->slug)) {
                $book->slug = Str::slug($book->title).'-'.Str::lower(Str::random(6));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(BorrowTransaction::class, BookCopy::class);
    }

    public function digitalAsset(): HasOne
    {
        return $this->hasOne(DigitalBookAsset::class);
    }

    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(BookAnnotation::class);
    }

    public function digitalLoans(): HasMany
    {
        return $this->hasMany(DigitalLoan::class);
    }

    public function activeDigitalLoans(): HasMany
    {
        return $this->digitalLoans()->active();
    }

    public function booklistEntries(): HasMany
    {
        return $this->hasMany(Booklist::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query, string $term): void {
            $query->where(function (Builder $query) use ($term): void {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('author', 'like', "%{$term}%")
                    ->orWhere('isbn', 'like', "%{$term}%")
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$term}%"));
            });
        });
    }

    public function refreshCopyCounters(): void
    {
        $this->forceFill([
            'total_copies' => $this->copies()->count(),
            'available_copies' => $this->copies()->where('status', BookCopy::STATUS_AVAILABLE)->count(),
        ])->save();
    }
}
