<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isMe = $this->resource->is($request->user());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'is_private' => (bool) $this->is_private,
            'is_me' => $isMe,
            'follow_status' => $isMe ? 'none' : match ($this->viewer_follow_status) {
                'accepted' => 'following',
                'pending' => 'pending',
                default => 'none',
            },
        ];
    }
}
