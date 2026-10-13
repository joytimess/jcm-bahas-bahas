<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ImageService
{
    public const MAX_IMAGES = 5;

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, int>  $removeImageIds
     */
    public function sync(Model $model, array $files, array $removeImageIds): void
    {
        if ($removeImageIds) {
            $model->images()->whereIn('id', $removeImageIds)->update(['is_deleted' => true]);
        }

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
