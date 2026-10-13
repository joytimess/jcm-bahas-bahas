<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\HasImageRules;
use App\Services\CommentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    use HasImageRules;

    public function authorize(): bool
    {
        app(CommentService::class)->assertVisible($this->user(), $this->route('thread'));

        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:280'],
            'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')
                ->where('thread_id', $this->route('thread')->id)->where('is_deleted', 0)],
        ] + $this->imageRules();
    }
}
