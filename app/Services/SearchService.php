<?php

namespace App\Services;

use App\Models\Billboard;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Support\Collection;
use ProtoneMedia\LaravelCrossEloquentSearch\Search;

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

        $results = Search::add(Client::class, ['name', 'email', 'company'])
            ->add(Contract::class, ['contract_number', 'notes'])
            ->add(Billboard::class, ['name', 'description', 'location'])
            ->beginWithWildcard()
            ->endWithWildcard()
            ->search($query);

        return $results->map(function ($model) {
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
