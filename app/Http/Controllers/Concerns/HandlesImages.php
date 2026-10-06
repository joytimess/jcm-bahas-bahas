<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait HandlesImages
{
    protected const MAX_IMAGES = 5;

    protected function imageRules(): array
    {
        return [
            'images' => ['sometimes', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_image_ids' => ['sometimes', 'array'],
            'remove_image_ids.*' => ['integer'],
        ];
    }

    /** Hapus gambar yang diminta (soft delete), lalu simpan upload baru dengan batas total 5. */
    protected function syncImages(Request $request, Model $model): void
    {
        if ($request->filled('remove_image_ids')) {
            $model->images()->whereIn('id', $request->input('remove_image_ids'))->update(['is_deleted' => true]);
        }

        $files = $request->file('images', []);

        if (! $files) {
            return;
        }

        $existing = $model->images()->count();

        if ($existing + count($files) > self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'images' => 'Maksimal '.self::MAX_IMAGES.' gambar per post.',
            ]);
        }

        foreach (array_values($files) as $i => $file) {
            $model->images()->create([
                'path' => $file->store('images/'.$model->getTable(), 'public'),
                'order' => $existing + $i,
            ]);
        }
    }
}
