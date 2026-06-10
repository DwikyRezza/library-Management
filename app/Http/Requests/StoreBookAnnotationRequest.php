<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookAnnotationRequest extends FormRequest
{
    private const MAX_PAYLOAD_BYTES = 1_000_000;

    public function authorize(): bool
    {
        return $this->user('member') !== null;
    }

    public function rules(): array
    {
        return [
            'page_number' => ['required', 'integer', 'min:1', 'max:100000'],
            'data' => ['required', 'array'],
            'data.version' => ['required', 'integer', 'in:1'],
            'data.annotations' => ['present', 'array', 'max:500'],
            'data.annotations.*' => ['required', 'array'],
            'data.annotations.*.id' => ['required', 'string', 'max:100', 'distinct'],
            'data.annotations.*.type' => [
                'required',
                'string',
                Rule::in(['pen', 'highlighter', 'text']),
            ],
            'data.annotations.*.color' => [
                'required',
                'string',
                'max:40',
                'regex:/^(#[0-9a-fA-F]{6,8}|rgba?\([0-9.,%\s]+\))$/',
            ],
            'data.annotations.*.brush_size' => [
                'required',
                'numeric',
                'between:0.0001,0.2',
            ],
            'data.annotations.*.points' => ['required', 'array', 'min:1', 'max:2000'],
            'data.annotations.*.points.*' => ['required', 'array'],
            'data.annotations.*.points.*.x' => ['required', 'numeric', 'between:0,1'],
            'data.annotations.*.points.*.y' => ['required', 'numeric', 'between:0,1'],
            'data.annotations.*.content' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('data.annotations', []) as $index => $annotation) {
                    if (
                        ($annotation['type'] ?? null) === 'text'
                        && blank($annotation['content'] ?? null)
                    ) {
                        $validator->errors()->add(
                            "data.annotations.{$index}.content",
                            'Konten catatan teks wajib diisi.',
                        );
                    }
                }

                $encoded = json_encode($this->input('data'), JSON_UNESCAPED_UNICODE);

                if ($encoded !== false && strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
                    $validator->errors()->add(
                        'data',
                        'Data anotasi per halaman terlalu besar.',
                    );
                }
            },
        ];
    }
}
