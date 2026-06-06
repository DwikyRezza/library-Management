<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('book_categories', 'name')->ignore($category)],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', Rule::in(['blue', 'emerald', 'amber', 'indigo', 'rose', 'slate'])],
        ];
    }
}
