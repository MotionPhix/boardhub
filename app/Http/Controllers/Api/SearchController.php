<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    public function __invoke(SearchRequest $request): JsonResponse
    {
        $results = $this->searchService->search($request->validated('q'));

        return response()->json([
            'data' => $results->map(fn ($result) => [
                'type' => $result['type'],
                'title' => $result['title'],
                'url' => $result['url'],
                'id' => $result['model']->id ?? null,
            ]),
            'count' => $results->count(),
        ]);
    }
}
