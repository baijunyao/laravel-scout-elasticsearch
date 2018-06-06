<?php

namespace Baijunyao\LaravelScoutElasticsearch\Console;

use Illuminate\Console\Command;
use Baijunyao\LaravelScoutElasticsearch\ElasticsearchClientTrait;

class ImportCommand extends Command
{
    use ElasticsearchClientTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:import {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $class = $this->argument('model');
        $model = new $class;

        // 获取需要索引的字段
        $columns = $model->toSearchableArray();

        // 如果没有定义索引字段；则索引全部字段
        if (empty($columns)) {
            $columns = $model->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($model->getTable());
            $columns = array_flip($columns);
        }

        // 分词器
        $analyzer = config('scout.elasticsearch.analyzer');
        $columns = collect($columns)->except('id', 'created_at', 'updated_at', 'deleted_at')
            ->transform(function () use ($analyzer) {
                return [
                    'type' => 'text',
                    'analyzer' => $analyzer
                ];
            });

        // 创建索引
        $index = [
            'index' => config('scout.elasticsearch.index')
        ];

        $settings = config('scout.elasticsearch.settings');

        if (! empty($settings)) {
            $index['body']['settings'] = $settings;
        }

        if (! empty($columns)) {
            $index['body']['mappings'][$model->searchableAs()] = [
                '_source' => array('enabled' => true),
                'properties' => $columns,
            ];
        }

        $client = $this->getElasticsearchClient();
        $client->indices()->create($index);

        // 导入数据
        $this->call('scout:import', [
            'model' => $class
        ]);
    }
}
