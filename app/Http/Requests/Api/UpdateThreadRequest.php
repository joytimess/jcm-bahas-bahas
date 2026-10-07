<?php

namespace App\Http\Requests\Api;

use App\Http\Controllers\Concerns\HandlesImages;
use Illuminate\Foundation\Http\FormRequest;

class UpdateThreadRequest extends FormRequest
{
    use HandlesImages;

    /** Hanya pemilik yang boleh mengedit, dan repost murni tidak bisa diedit. */
    public function authorize(): bool
    {
        $thread = $this->route('thread');

        return $thread->user_id === $this->user()?->id && $thread->type !== 'repost';
    }

    public function rules(): array
    {
        return ['body' => ['sometimes', 'required', 'string', 'max:280']] + $this->imageRules();
    }
}
