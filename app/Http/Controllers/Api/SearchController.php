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
    public function __construct(private SearchService $search) {}

    public function index(SearchRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $type = $data['type'] ?? 'threads';
        $page = (int) ($data['page'] ?? 1);

        $results = $type === 'users'
            ? $this->search->users($request->user(), $data['q'], $page)
            : $this->search->threads($request->user(), $data['q'], $page);

        $results->appends($request->safe()->except('page'));

        return $type === 'users'
            ? SearchUserResource::collection($results)
            : ThreadResource::collection($results);
    }
}
