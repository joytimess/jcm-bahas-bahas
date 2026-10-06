<?php

namespace App\Models;

use App\Models\Concerns\FlagsDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    use FlagsDeleted;

    protected $fillable = ['body', 'parent_id'];

    protected $casts = ['is_deleted' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('order');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /** Tandai comment, seluruh balasannya (rekursif), dan images-nya sebagai deleted. */
    public function softDelete(): bool
    {
        $ids = [$this->id];
        $level = [$this->id];

        while ($level) {
            $level = Comment::whereIn('parent_id', $level)->pluck('id')->all();
            $ids = array_merge($ids, $level);
        }

        Image::where('imageable_type', $this->getMorphClass())->whereIn('imageable_id', $ids)->update(['is_deleted' => true]);
        Comment::whereIn('id', $ids)->update(['is_deleted' => true]);

        $this->is_deleted = true;

        return true;
    }
}
