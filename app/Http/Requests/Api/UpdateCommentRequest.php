<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\HasImageRules;
use App\Services\CommentService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    use HasImageRules;

    public function authorize(): bool
    {
        app(CommentService::class)->assertOwner($this->user(), $this->route('comment'));

        return true;
    }

    public function rules(): array
    {
        return ['body' => ['sometimes', 'required', 'string', 'max:280']] + $this->imageRules();
    }
}
