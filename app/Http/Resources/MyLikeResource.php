<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class MyLikeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->likeable;
        $isComment = $item instanceof Comment;
        $thread = $isComment ? $item->thread : $item;

        return [
            'id' => $this->id,
            'type' => $isComment ? 'comment' : 'thread',
            'liked_at' => $this->created_at,
            'thread' => [
                'id' => $thread->id,
                'body' => Str::limit($thread->body, $isComment ? 120 : 280),
                'user' => new UserBriefResource($thread->user),
            ],
            'comment' => $isComment ? [
                'id' => $item->id,
                'body' => $item->body,
                'user' => new UserBriefResource($item->user),
            ] : null,
        ];
    }
}
