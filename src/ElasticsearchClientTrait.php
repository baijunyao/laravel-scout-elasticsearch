<?php

namespace Baijunyao\LaravelScoutElasticsearch;

use Elasticsearch\ClientBuilder;

trait ElasticsearchClientTrait
{
    /**
     * Get ElasticSearch Client
     *
     * @return \Elasticsearch\Client
     */
    public function getElasticsearchClient()
    {
        $hosts = config('scout.elasticsearch.hosts');
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        return $client;
    }
}
