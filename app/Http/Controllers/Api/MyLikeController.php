<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MyLikeResource;
use App\Http\Resources\PaginatedListResource;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyLikeController extends Controller
{
    public function __construct(private LikeService $likes) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(new PaginatedListResource($this->likes->history($request->user()), MyLikeResource::class));
    }
}
