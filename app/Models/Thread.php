<?php

namespace App\Models;

use App\Models\Concerns\FlagsDeleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Thread extends Model
{
    use FlagsDeleted;

    protected $fillable = ['body'];

    protected $casts = ['is_deleted' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Thread yang boleh dilihat $viewer: miliknya, akun publik, atau akun privat yang ia ikuti (disetujui). */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        return $query->where(function (Builder $q) use ($viewer) {
            $q->where('threads.user_id', $viewer->id)
                ->orWhereHas('user', fn ($u) => $u->where('is_private', false))
                ->orWhereHas('user.followers', fn ($f) => $f->where('follows.follower_id', $viewer->id));
        });
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('order');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /** Tandai thread beserta seluruh comments dan images-nya sebagai deleted. */
    public function softDelete(): bool
    {
        $commentIds = Comment::where('thread_id', $this->id)->pluck('id');

        Image::where('imageable_type', (new Comment)->getMorphClass())
            ->whereIn('imageable_id', $commentIds)
            ->update(['is_deleted' => true]);
        Comment::whereIn('id', $commentIds)->update(['is_deleted' => true]);
        $this->images()->update(['is_deleted' => true]);

        return $this->forceFill(['is_deleted' => true])->save();
    }
}
