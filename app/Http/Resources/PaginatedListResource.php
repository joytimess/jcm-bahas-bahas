<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class PaginatedListResource extends JsonResource
{
    /**
     * Preserve the flat paginator envelope used by existing list endpoints.
     *
     * @param  class-string<JsonResource>  $itemResource
     */
    public function __construct(LengthAwarePaginator $resource, private string $itemResource)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $data = $this->resource->toArray();
        $data['data'] = $this->itemResource::collection($this->resource->getCollection())->resolve($request);

        return $data;
    }
}
