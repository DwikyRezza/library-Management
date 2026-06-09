<?php

namespace App\Http\Requests;

use App\Services\DigitalLoanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookHighlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('member') !== null;
    }

    public function rules(): array
    {
        return [
            'digital_loan_id' => ['required', 'integer'],
            'page_number' => ['required', 'integer', 'min:1', 'max:100000'],
            'highlighted_text' => ['required', 'string', 'max:5000'],
            'color' => ['required', 'string', Rule::in(DigitalLoanService::HIGHLIGHT_COLORS)],
            'serialized_range' => ['required', 'array'],
            'serialized_range.version' => ['required', 'integer', 'in:1'],
            'serialized_range.start' => ['required', 'array'],
            'serialized_range.start.index' => ['required', 'integer', 'min:0', 'max:100000'],
            'serialized_range.start.offset' => ['required', 'integer', 'min:0', 'max:100000'],
            'serialized_range.end' => ['required', 'array'],
            'serialized_range.end.index' => ['required', 'integer', 'min:0', 'max:100000'],
            'serialized_range.end.offset' => ['required', 'integer', 'min:0', 'max:100000'],
            'serialized_range.rects' => ['required', 'array', 'min:1', 'max:200'],
            'serialized_range.rects.*' => ['required', 'array'],
            'serialized_range.rects.*.x' => ['required', 'numeric', 'between:0,1'],
            'serialized_range.rects.*.y' => ['required', 'numeric', 'between:0,1'],
            'serialized_range.rects.*.width' => ['required', 'numeric', 'gt:0', 'max:1'],
            'serialized_range.rects.*.height' => ['required', 'numeric', 'gt:0', 'max:1'],
        ];
    }
}
