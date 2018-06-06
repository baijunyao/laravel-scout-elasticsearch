<?php

namespace Baijunyao\LaravelScoutElasticsearch\Console;

use Illuminate\Console\Command;
use Baijunyao\LaravelScoutElasticsearch\ElasticsearchClientTrait;

class FlushCommand extends Command
{
    use ElasticsearchClientTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:flush {model}';

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
        $this->call('scout:flush', [
            'model' => $class
        ]);
        $model = new $class;
        $index = [
            'index' => config('scout.elasticsearch.index')
        ];
        $client = $this->getElasticsearchClient();
        $client->indices()->delete($index);
    }
}
