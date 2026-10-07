<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'body' => $this->body,
            'user' => new UserBriefResource($this->user),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'comments_count' => $this->whenCounted('comments'),
            'likes_count' => $this->whenCounted('likes'),
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            'reposts_count' => $this->whenCounted('reposts'),
            'quotes_count' => $this->whenCounted('quotes'),
            'reposted_by_me' => (bool) ($this->reposted_by_me ?? false),
            'can_repost' => ! ($this->user->is_private ?? false),
            // Hanya satu level: thread asli tidak membawa repost_of lagi.
            'repost_of' => $this->when($this->relationLoaded('repostOf'), fn () => $this->repostOf ? new self($this->repostOf) : null),
            'repost_unavailable' => $this->repost_of_id !== null && $this->relationLoaded('repostOf') && $this->repostOf === null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
