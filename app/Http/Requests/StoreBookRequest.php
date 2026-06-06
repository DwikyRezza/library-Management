<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'between:1000,2100'],
            'isbn' => ['nullable', 'string', 'max:50', 'unique:books,isbn'],
            'category_id' => ['required', 'exists:book_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'number_of_copies' => ['required', 'integer', 'min:1', 'max:200'],
            'shelf_location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
