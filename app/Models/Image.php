<?php

namespace App\Models;

use App\Models\Concerns\FlagsDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use FlagsDeleted;

    protected $fillable = ['path', 'order'];

    protected $casts = ['is_deleted' => 'boolean'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
