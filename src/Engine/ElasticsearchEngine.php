<?php

namespace Baijunyao\LaravelScoutElasticsearch\Engine;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Illuminate\Database\Eloquent\Collection;
use Baijunyao\LaravelScoutElasticsearch\ElasticsearchClientTrait;

class ElasticsearchEngine extends Engine
{
    use ElasticsearchClientTrait;

    /**
     * Elastic where the instance of Elastic|\Elasticsearch\Client is stored.
     *
     * @var object
     */
    protected $elastic;

    /**
     * ElasticsearchEngine constructor.
     */
    public function __construct()
    {
        $this->elastic = $this->getElasticsearchClient();
    }

    /**
     * Update the given model in the index.
     *
     * @param  Collection  $models
     * @return void
     */
    public function update($models)
    {
        $params['body'] = [];

        $models->each(function($model) use (&$params)
        {
            $type = $model->searchableAs();
            $index = config('scout.elasticsearch.prefix').$type;
            $params['body'][] = [
                'update' => [
                    '_id' => $model->getKey(),
                    '_index' => $index,
                    '_type' => $type,
                ]
            ];
            $doc = collect($model->toSearchableArray())->except(['created_at', 'updated_at', 'deleted_at']);
            $params['body'][] = [
                'doc' => $doc,
                'doc_as_upsert' => true
            ];
        });

        $this->elastic->bulk($params);
    }

    /**
     * Remove the given model from the index.
     *
     * @param  Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $params['body'] = [];

        $models->each(function($model) use (&$params)
        {
            $type = $model->searchableAs();
            $index = config('scout.elasticsearch.prefix').$type;
            $params['body'][] = [
                'delete' => [
                    '_id' => $model->getKey(),
                    '_index' => $index,
                    '_type' => $type,
                ]
            ];
        });

        $this->elastic->bulk($params);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder  $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'numericFilters' => $this->filters($builder),
            'size' => $builder->limit,
        ]));
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder  $builder
     * @param  int  $perPage
     * @param  int  $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $result = $this->performSearch($builder, [
            'numericFilters' => $this->filters($builder),
            'from' => (($page * $perPage) - $perPage),
            'size' => $perPage,
        ]);

       $result['nbPages'] = $result['hits']['total']/$perPage;

        return $result;
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder  $builder
     * @param  array  $options
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $type = $builder->model->searchableAs();
        $filter = config('scout.elasticsearch.filter');
        $query = str_replace($filter, '', $builder->query);
        $index = config('scout.elasticsearch.prefix').$type;
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'query_string' => [
                                    'query' => $query
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if ($sort = $this->sort($builder)) {
            $params['body']['sort'] = $sort;
        }

        if (isset($options['from'])) {
            $params['body']['from'] = $options['from'];
        }

        if (isset($options['size'])) {
            $params['body']['size'] = $options['size'];
        }

        if (isset($options['numericFilters']) && count($options['numericFilters'])) {
            $params['body']['query']['bool']['must'] = array_merge($params['body']['query']['bool']['must'],
                $options['numericFilters']);
        }

        if ($builder->callback) {
            return call_user_func(
                $builder->callback,
                $this->elastic,
                $builder->query,
                $params
            );
        }

        $result = $this->elastic->search($params);

        if (is_array($result['hits']['total'])) {
            $result['hits']['total'] = $result['hits']['total']['value'];
        }

        return $result;
    }

    /**
     * Get the filter array for the query.
     *
     * @param  Builder  $builder
     * @return array
     */
    protected function filters(Builder $builder)
    {
        return collect($builder->wheres)->map(function ($value, $key) {
            if (is_array($value)) {
                return ['terms' => [$key => $value]];
            }

            return ['match_phrase' => [$key => $value]];
        })->values()->all();
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id')->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param Builder $builder
     * @param mixed $results
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($results['hits']['total'] === 0) {
            return Collection::make();
        }

        $keys = collect($results['hits']['hits'])
                        ->pluck('_id')->values()->all();

        $models = $model->whereIn(
            $model->getKeyName(), $keys
        )->get()->keyBy($model->getKeyName());

        return collect($results['hits']['hits'])->map(function ($hit) use ($model, $models) {
            return isset($models[$hit['_id']]) ? $models[$hit['_id']] : null;
        })->filter()->values();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total'];
    }

    public function flush($model)
    {
        $this->elastic->indices()->delete([
            'index' => config('scout.elasticsearch.prefix') . $model->searchableAs()
        ]);
    }

    /**
     * Generates the sort if theres any.
     *
     * @param  Builder $builder
     * @return array|null
     */
    protected function sort($builder)
    {
        if (count($builder->orders) == 0) {
            return null;
        }
        return collect($builder->orders)->map(function($order) {
            return [$order['column'] => $order['direction']];
        })->toArray();
    }
}
