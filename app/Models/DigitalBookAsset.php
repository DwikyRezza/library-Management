<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DigitalBookAsset extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'book_id',
        'original_path',
        'storage_disk',
        'pages_path',
        'original_name',
        'mime_type',
        'file_size',
        'sha256',
        'page_count',
        'status',
        'last_error',
        'uploaded_by',
        'rendered_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'page_count' => 'integer',
        'rendered_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY && filled($this->original_path);
    }
}
