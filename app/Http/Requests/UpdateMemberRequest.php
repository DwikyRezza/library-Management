<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $member = $this->route('member');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('members', 'email')->ignore($member)],
            'phone' => ['nullable', 'string', 'max:50'],
            'roll_number' => ['required', 'string', 'max:100', Rule::unique('members', 'roll_number')->ignore($member)],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'year' => ['nullable', 'integer', 'between:1,8'],
            'member_category_id' => ['required', 'exists:member_categories,id'],
        ];
    }
}
