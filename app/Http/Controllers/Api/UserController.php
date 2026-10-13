<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeResource;
use App\Http\Resources\PaginatedListResource;
use App\Http\Resources\SuggestedUserResource;
use App\Http\Resources\ThreadResource;
use App\Http\Resources\UserBriefResource;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private UserService $users) {}

    public function me(Request $request): JsonResponse
    {
        return response()->json(new MeResource($this->users->me($request->user())));
    }

    public function show(Request $request, User $user): JsonResponse
    {
        return response()->json(['data' => new UserProfileResource($this->users->show($request->user(), $user))]);
    }

    public function threads(Request $request, User $user): AnonymousResourceCollection
    {
        return ThreadResource::collection($this->users->threads($request->user(), $user));
    }

    public function followers(Request $request, User $user): JsonResponse
    {
        return response()->json(new PaginatedListResource(
            $this->users->followers($request->user(), $user, (string) $request->query('search', '')), UserBriefResource::class
        ));
    }

    public function following(Request $request, User $user): JsonResponse
    {
        return response()->json(new PaginatedListResource(
            $this->users->following($request->user(), $user, (string) $request->query('search', '')), UserBriefResource::class
        ));
    }

    public function suggestions(Request $request): JsonResponse
    {
        return response()->json(['data' => SuggestedUserResource::collection($this->users->suggestions($request->user()))]);
    }
}
