<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowTransaction extends Model
{
    use HasFactory;

    public const STATUS_BORROWED = 'borrowed';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_OVERDUE = 'overdue';

    public const ACTIVE_STATUSES = [
        self::STATUS_BORROWED,
        self::STATUS_OVERDUE,
    ];

    protected $fillable = [
        'transaction_code',
        'book_copy_id',
        'member_id',
        'issued_by',
        'returned_by',
        'issued_at',
        'due_at',
        'returned_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->returned_at === null && $this->due_at instanceof CarbonInterface && $this->due_at->isPast(),
        );
    }

    public function daysOverdue(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->is_overdue ? max(1, (int) $this->due_at->diffInDays(now())) : 0,
        );
    }

    public function displayStatus(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->is_overdue && $this->status === self::STATUS_BORROWED
                ? self::STATUS_OVERDUE
                : $this->status,
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at')->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('returned_at')->where('due_at', '<', now())->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            self::STATUS_BORROWED => $query->where('status', self::STATUS_BORROWED)->where('due_at', '>=', now()),
            self::STATUS_RETURNED => $query->where('status', self::STATUS_RETURNED),
            self::STATUS_OVERDUE => $query->overdue(),
            default => $query,
        };
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query, string $term): void {
            $query->where(function (Builder $query) use ($term): void {
                $query->where('transaction_code', 'like', "%{$term}%")
                    ->orWhereHas('bookCopy', fn (Builder $copy) => $copy->where('copy_code', 'like', "%{$term}%"))
                    ->orWhereHas('bookCopy.book', fn (Builder $book) => $book->where('title', 'like', "%{$term}%"))
                    ->orWhereHas('member', function (Builder $member) use ($term): void {
                        $member->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('member_code', 'like', "%{$term}%")
                            ->orWhere('roll_number', 'like', "%{$term}%");
                    });
            });
        });
    }
}
