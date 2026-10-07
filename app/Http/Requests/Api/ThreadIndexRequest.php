<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ThreadIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function validationData(): array
    {
        return $this->query->all();
    }

    public function rules(): array
    {
        return [
            'feed' => ['sometimes', 'required', 'string', Rule::in(['all', 'following'])],
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
        ];
    }
}
