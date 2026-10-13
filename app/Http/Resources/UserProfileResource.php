<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'is_private' => $this->is_private,
            'is_me' => $this->is_me,
            'followers_count' => $this->followers_count,
            'following_count' => $this->following_count,
            'follow_status' => $this->follow_status,
            'can_view_threads' => $this->can_view_threads,
        ];
    }
}
