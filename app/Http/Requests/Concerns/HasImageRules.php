<?php

namespace App\Http\Requests\Concerns;

use App\Services\ImageService;

trait HasImageRules
{
    protected function imageRules(): array
    {
        return [
            'images' => ['sometimes', 'array', 'max:'.ImageService::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_image_ids' => ['sometimes', 'array'],
            'remove_image_ids.*' => ['integer'],
        ];
    }
}
