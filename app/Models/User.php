<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    /** Pengguna yang mengikuti user ini (hanya yang sudah disetujui). */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'following_id', 'follower_id')
            ->wherePivot('status', 'accepted')->withTimestamps();
    }

    /** Pengguna yang diikuti user ini (hanya yang sudah disetujui). */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'follower_id', 'following_id')
            ->wherePivot('status', 'accepted')->withTimestamps();
    }

    /** Pemohon follow yang menunggu persetujuan user ini. */
    public function followRequests(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'following_id', 'follower_id')
            ->wherePivot('status', 'pending')->withTimestamps();
    }

    /** Status follow $viewer terhadap user ini: none | pending | following. */
    public function followStatusFor(self $viewer): string
    {
        $status = DB::table('follows')
            ->where('follower_id', $viewer->id)
            ->where('following_id', $this->id)
            ->value('status');

        return match ($status) {
            'accepted' => 'following',
            'pending' => 'pending',
            default => 'none',
        };
    }

    /** Apakah thread milik user ini boleh dilihat $viewer? Satu sumber kebenaran privasi. */
    public function threadsVisibleTo(self $viewer): bool
    {
        return $viewer->id === $this->id
            || ! $this->is_private
            || $this->followStatusFor($viewer) === 'following';
    }

    public function canView(Thread $thread): bool
    {
        return $thread->user->threadsVisibleTo($this);
    }

    /** URL foto profil, atau null bila belum upload. */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar ? Storage::disk('public')->url($this->avatar) : null);
    }

    /** Inisial (maks. 2 huruf) untuk avatar cadangan. */
    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $words = preg_split('/\s+/', trim($this->name)) ?: [];

            return mb_strtoupper(collect($words)->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
        });
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_private' => 'boolean',
        ];
    }
}
