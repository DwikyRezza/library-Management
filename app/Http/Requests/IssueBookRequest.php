<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssueBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'book_copy_code' => ['required', 'string', 'max:100'],
            'member_lookup' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
