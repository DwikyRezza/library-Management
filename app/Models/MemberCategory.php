<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'max_books',
        'loan_days',
        'description',
    ];

    protected $casts = [
        'max_books' => 'integer',
        'loan_days' => 'integer',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
