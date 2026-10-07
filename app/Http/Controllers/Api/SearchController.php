<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchRequest;
use App\Http\Resources\SearchUserResource;
use App\Http\Resources\ThreadResource;
use App\Services\SearchService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    public function index(SearchRequest $request, SearchService $search): AnonymousResourceCollection
    {
        $data = $request->validated();
        $type = $data['type'] ?? 'threads';
        $page = (int) ($data['page'] ?? 1);

        $results = $type === 'users'
            ? $search->users($request->user(), $data['q'], $page)
            : $search->threads($request->user(), $data['q'], $page);

        $results->appends($request->safe()->except('page'));

        return $type === 'users'
            ? SearchUserResource::collection($results)
            : ThreadResource::collection($results);
    }
}
