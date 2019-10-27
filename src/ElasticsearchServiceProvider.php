<?php

namespace Baijunyao\LaravelScoutElasticsearch;

use Laravel\Scout\EngineManager;
use Illuminate\Support\ServiceProvider;
use Baijunyao\LaravelScoutElasticsearch\Engine\ElasticsearchEngine;
use Baijunyao\LaravelScoutElasticsearch\Console\ImportCommand;
use Baijunyao\LaravelScoutElasticsearch\Console\FlushCommand;

class ElasticsearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        app(EngineManager::class)->extend('elasticsearch', function($app) {
            return new ElasticsearchEngine();
        });
    }

    /**
     * 在容器中注册绑定。
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/laravel-scout-elasticsearch.php', 'scout'
        );
    }
}
