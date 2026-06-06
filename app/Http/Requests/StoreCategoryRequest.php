<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:book_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', Rule::in(['blue', 'emerald', 'amber', 'indigo', 'rose', 'slate'])],
        ];
    }
}
