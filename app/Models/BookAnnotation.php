<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookAnnotation extends Model
{
    protected $fillable = [
        'book_id',
        'member_id',
        'page_number',
        'data',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'data' => 'array',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
