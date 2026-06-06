<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookCopy extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_LOST = 'lost';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_BORROWED,
        self::STATUS_MAINTENANCE,
        self::STATUS_LOST,
    ];

    protected $fillable = [
        'book_id',
        'copy_code',
        'shelf_location',
        'status',
        'condition_note',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BorrowTransaction::class);
    }

    public function activeTransaction(): HasOne
    {
        return $this->hasOne(BorrowTransaction::class)
            ->whereNull('returned_at')
            ->whereIn('status', BorrowTransaction::ACTIVE_STATUSES)
            ->latestOfMany();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
