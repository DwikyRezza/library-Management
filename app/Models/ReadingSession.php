<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingSession extends Model
{
    protected $fillable = [
        'uuid',
        'member_id',
        'book_id',
        'digital_book_asset_id',
        'started_at',
        'last_active_at',
        'ended_at',
        'last_page',
        'max_page',
        'duration_seconds',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_active_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_page' => 'integer',
        'max_page' => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function digitalBookAsset(): BelongsTo
    {
        return $this->belongsTo(DigitalBookAsset::class);
    }
}
