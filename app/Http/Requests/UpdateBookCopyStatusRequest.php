<?php

namespace App\Http\Requests;

use App\Models\BookCopy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookCopyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                BookCopy::STATUS_AVAILABLE,
                BookCopy::STATUS_MAINTENANCE,
                BookCopy::STATUS_LOST,
            ])],
            'condition_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
