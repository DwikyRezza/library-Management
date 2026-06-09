<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalLoan extends Model
{
    use HasFactory;

    public const RETURN_MANUAL = 'manual';

    public const RETURN_EXPIRED = 'expired';

    protected $fillable = [
        'member_id',
        'book_id',
        'book_copy_id',
        'borrowed_at',
        'due_at',
        'last_read_page',
        'extended_at',
        'returned_at',
        'return_reason',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'last_read_page' => 'integer',
        'extended_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(BookHighlight::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('returned_at')
            ->where('due_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNull('returned_at')
            ->where('due_at', '<=', now());
    }

    public function isActive(): bool
    {
        return $this->returned_at === null
            && $this->due_at instanceof CarbonInterface
            && $this->due_at->isFuture();
    }

    public function canExtend(): bool
    {
        return $this->isActive()
            && $this->extended_at === null
            && $this->due_at->lessThanOrEqualTo(now()->addDay());
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->isActive();
    }

    public function getCanExtendAttribute(): bool
    {
        return $this->canExtend();
    }

    protected function remainingSeconds(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->isActive()
                ? max(0, (int) now()->diffInSeconds($this->due_at))
                : 0,
        );
    }
}
