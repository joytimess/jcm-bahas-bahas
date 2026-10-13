<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\HasImageRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreThreadRequest extends FormRequest
{
    use HasImageRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:280'],
            'quote_of' => ['sometimes', 'nullable', 'integer'],
        ] + $this->imageRules();
    }
}
