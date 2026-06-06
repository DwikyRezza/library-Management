<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:members,username'],
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'roll_number' => ['required', 'string', 'max:100', 'unique:members,roll_number'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'year' => ['nullable', 'integer', 'between:1,8'],
            'member_category_id' => ['required', 'exists:member_categories,id'],
        ];
    }
}
