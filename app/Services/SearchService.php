<?php

namespace App\Services;

use App\Models\Billboard;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * Perform a cross-eloquent search across key models.
     *
     * @return Collection<int, array{type:string, title:string, url:?string, model:mixed}>
     */
    public function search(string $query): Collection
    {
        if (trim($query) === '') {
            return collect();
        }

        $searchTerm = '%' . trim($query) . '%';

        // Search clients
        $clients = Client::query()
            ->where('name', 'like', $searchTerm)
            ->orWhere('email', 'like', $searchTerm)
            ->orWhere('company', 'like', $searchTerm)
            ->get();

        // Search contracts
        $contracts = Contract::query()
            ->where('contract_number', 'like', $searchTerm)
            ->orWhere('notes', 'like', $searchTerm)
            ->get();

        // Search billboards
        $billboards = Billboard::query()
            ->where('name', 'like', $searchTerm)
            ->orWhere('description', 'like', $searchTerm)
            ->orWhere('location', 'like', $searchTerm)
            ->get();

        // Merge and map results
        return $clients->concat($contracts)
            ->concat($billboards)
            ->sortByDesc('updated_at')
            ->values()
            ->map(function ($model) {
                return [
                    'type' => class_basename($model),
                    'title' => $this->titleFor($model),
                    'url' => method_exists($model, 'url') ? $model->url() : null,
                    'model' => $model,
                ];
            });
    }

    /**
     * Create a human friendly title for a result model.
     */
    protected function titleFor(mixed $model): string
    {
        return match (true) {
            $model instanceof Client => $model->name.' ('.($model->company ?? 'Client').')',
            $model instanceof Contract => 'Contract '.($model->contract_number ?? '#'),
            $model instanceof Billboard => 'Billboard: '.($model->name ?? '#').' at '.($model->location ?? 'Unknown'),
            default => class_basename($model).' #'.($model->id ?? ''),
        };
    }
}
