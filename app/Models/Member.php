<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'member_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'roll_number',
        'branch_id',
        'year',
        'member_category_id',
        'approved',
        'rejected',
        'approved_at',
        'rejected_at',
        'books_borrowed_count',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'rejected' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'books_borrowed_count' => 'integer',
        'year' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function memberCategory(): BelongsTo
    {
        return $this->belongsTo(MemberCategory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BorrowTransaction::class);
    }

    public function activeTransactions(): HasMany
    {
        return $this->transactions()->active();
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim($this->first_name.' '.$this->last_name),
        );
    }

    public function status(): Attribute
    {
        return Attribute::make(get: function (): string {
            if ($this->approved) {
                return self::STATUS_APPROVED;
            }

            if ($this->rejected) {
                return self::STATUS_REJECTED;
            }

            return self::STATUS_PENDING;
        });
    }

    public function canBorrowMore(): bool
    {
        return $this->approved
            && ! $this->rejected
            && $this->books_borrowed_count < $this->memberCategory->max_books;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $query, string $term): void {
            $query->where(function (Builder $query) use ($term): void {
                $query->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('roll_number', 'like', "%{$term}%")
                    ->orWhere('member_code', 'like', "%{$term}%");
            });
        });
    }

    public function scopeApprovalStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            self::STATUS_APPROVED => $query->where('approved', true)->where('rejected', false),
            self::STATUS_REJECTED => $query->where('rejected', true),
            self::STATUS_PENDING => $query->where('approved', false)->where('rejected', false),
            default => $query,
        };
    }
}
