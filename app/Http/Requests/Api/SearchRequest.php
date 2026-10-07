<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function validationData(): array
    {
        $data = $this->query->all();

        if (isset($data['q']) && is_string($data['q'])) {
            $data['q'] = trim($data['q']);
        }

        return $data;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['sometimes', 'required', 'string', Rule::in(['threads', 'users'])],
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
        ];
    }
}
