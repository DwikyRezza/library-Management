<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookHighlight extends Model
{
    protected $fillable = [
        'digital_loan_id',
        'page_number',
        'highlighted_text',
        'color',
        'serialized_range',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'serialized_range' => 'array',
    ];

    public function digitalLoan(): BelongsTo
    {
        return $this->belongsTo(DigitalLoan::class);
    }
}
