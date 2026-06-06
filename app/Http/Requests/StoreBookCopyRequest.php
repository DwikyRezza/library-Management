<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'number_of_copies' => ['required', 'integer', 'min:1', 'max:200'],
            'shelf_location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
