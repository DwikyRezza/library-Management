<?php

namespace App\Http\Requests;

use App\Models\ReadingSession;
use Illuminate\Foundation\Http\FormRequest;

class ReadingHeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('readingSession');

        return $session instanceof ReadingSession
            && $session->member_id === $this->user('member')?->id;
    }

    public function rules(): array
    {
        return [
            'page' => ['required', 'integer', 'min:1'],
        ];
    }
}
