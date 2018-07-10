### 教程
[laravel下elasticsearch+analysis-ik实现中文全文搜索](https://baijunyao.com/article/156)

### 介绍
为 [Laravel Scout](https://laravel-china.org/docs/laravel/5.5/scout/1346) 开发的 [Elasticsearch](https://baijunyao.com/article/155) 驱动；  
之所以造这个轮子是因为 [laravel-scout-elastic](https://github.com/ErickTamayo/laravel-scout-elastic) 不支持中文分词且不支持多张表；  
而 [Elasticquent](https://github.com/elasticquent/Elasticquent) 这种不基于 scout 的又略麻烦；  

### 安装使用
```bash
composer require baijunyao/laravel-scout-elasticsearch
```
添加 Provider ；  
config/app.php  
```php
'providers' => [

    // ...

    /**
     * Elasticsearch全文搜索
     */
    Laravel\Scout\ScoutServiceProvider::class,
    Baijunyao\LaravelScoutElasticsearch\ElasticsearchServiceProvider::class,
],
```
发布配置项;  
```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```
增加配置项；  
.env ;
```bash
SCOUT_DRIVER=elasticsearch
```
模型中定义全文搜索；  
此处以文章表为示例；  
app/Models/Article.php
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Article extends Model
{
    use Searchable;

    /**
     * 索引的字段
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return $this->only('id', 'title', 'content');
    }
}
```
生成索引；  
```bash
php artisan elasticsearch:import "App\Models\Article"
```
使用起来也相当简单；  
只需要把要搜索的内容传给 search() 方法即可;  
routes/web.php  
```php
<?php
use App\Models\Article;

Route::get('search', function () {
    // 为查看方便都转成数组
    dump(Article::all()->toArray());
    dump(Article::search('功能齐全的搜索引擎')->get()->toArray());
});
```

默认使用 analysis-ik 作为分词器；  
如果需要自定义配置；  
config/scout.php 
```php
    'elasticsearch' => [
        'prefix' => env('ELASTICSEARCH_PREFIX', 'laravel_'),
        'hosts' => [
            env('ELASTICSEARCH_HOST', 'http://localhost'),
        ],
        'analyzer' => env('ELASTICSEARCH_ANALYZER', 'ik_max_word'),
        'settings' => [],
        'filter' => [
            '+',
            '-',
            '&',
            '|',
            '!',
            '(',
            ')',
            '{',
            '}',
            '[',
            ']',
            '^',
            '\\',
            '"',
            '~',
            '*',
            '?',
            ':'
        ]
    ]
```

### 链接
- 博客：[https://baijunyao.com](http://baijunyao.com)   
- github：[https://github.com/baijunyao/laravel-bjyblog](https://github.com/baijunyao/laravel-bjyblog)   
- 码云：[https://gitee.com/baijunyao/laravel-bjyblog](https://gitee.com/baijunyao/laravel-bjyblog)


