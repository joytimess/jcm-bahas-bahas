<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thread_id' => $this->thread_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'user' => new UserBriefResource($this->user),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'likes_count' => $this->whenCounted('likes'),
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            'replies' => self::collection($this->whenLoaded('replyTree')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
