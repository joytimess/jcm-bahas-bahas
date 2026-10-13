<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\HasImageRules;
use App\Services\ThreadService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateThreadRequest extends FormRequest
{
    use HasImageRules;

    /** Hanya pemilik yang boleh mengedit, dan repost murni tidak bisa diedit. */
    public function authorize(): bool
    {
        $thread = $this->route('thread');

        app(ThreadService::class)->assertEditable($this->user(), $thread);

        return true;
    }

    public function rules(): array
    {
        return ['body' => ['sometimes', 'required', 'string', 'max:280']] + $this->imageRules();
    }
}
