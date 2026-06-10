<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListBookAnnotationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('member') !== null;
    }

    public function rules(): array
    {
        return [
            'start' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'end' => ['nullable', 'integer', 'min:1', 'max:100000', 'gte:start'],
        ];
    }
}
