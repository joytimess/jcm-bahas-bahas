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

    protected $fillable = ['body', 'repost_of_id'];

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

    /** Thread asli yang di-repost atau di-quote. */
    public function repostOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'repost_of_id');
    }

    /** Repost murni (tanpa body) atas thread ini. */
    public function reposts(): HasMany
    {
        return $this->hasMany(self::class, 'repost_of_id')->whereNull('body');
    }

    /** Quote (repost dengan body) atas thread ini. */
    public function quotes(): HasMany
    {
        return $this->hasMany(self::class, 'repost_of_id')->whereNotNull('body');
    }

    /** post | repost | quote */
    public function getTypeAttribute(): string
    {
        if (! $this->repost_of_id) {
            return 'post';
        }

        return $this->body === null ? 'repost' : 'quote';
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
