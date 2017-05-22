Elasticsearch Extension for Yii2
==================
Yii2 extension for integration with Elasticsearch version 5.0 and above, based on official [elasticsearch-php](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html) low-level client. 

Elasticsearch 5.0 came out with a bunch of [new features and improvements](https://www.elastic.co/guide/en/elasticsearch/reference/5.4/es-release-notes.html) which is intriguing. Sadly, but Yii2 official extension support is limited to versions from 1.0 to 2.4.

Compared to [elasticsearch-php](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html) this extension has more intuitive way of doing things, like index documents, search, percolate (reverse search), building complex filter conditions using simple SQL-like language. Also it's highly configurable and extensible, it's not tightly tied to ActiveRecord models, but this can be easily implemented.

The documentation is relevant for the latest stable version of the application.

[![Latest Stable Version](https://poser.pugx.org/mikemadisonweb/yii2-elasticsearch/v/stable)](https://packagist.org/packages/mikemadisonweb/yii2-elasticsearch)
[![License](https://poser.pugx.org/mikemadisonweb/yii2-elasticsearch/license)](https://packagist.org/packages/mikemadisonweb/yii2-elasticsearch)

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
php composer.phar require mikemadisonweb/yii2-elasticsearch
```
or add
```json
"mikemadisonweb/yii2-elasticsearch": "^1.1.1"
```
to the require section of your `composer.json` file.

Configuration
-------------
Lets suppose that you have huge database of blog posts and you want to use full-text search on title and body fields, also search for keywords as is and maybe filter by categories and tags:
```php
<?php
// config/main.php
return [
    // ...
    'components'    => [
        // ...
        'elasticsearch'  => [
            'class' => \mikemadisonweb\elasticsearch\Configuration::class,
            'clients' => [
                'default' => [
                    'hosts' => [
                        'server1.yourdomain.com',
                        'server2.yourdomain.com:9200', 
                    ],
                ],
            ],
            'indexes' => [
                [
                    'index' => 'my-blog',
                    'client' => [
                        'name' => 'default',
                    ],
                    'body' => [
                        'settings' => [
                            'number_of_shards' => 5,
                            'number_of_replicas' => 1,
                        ],
                        'mappings' => [
                            'posts' => [
                                'dynamic' => 'strict', // Validate upon indexing, optional
                                'properties' => [
                                    'title' => [
                                        'type' => 'text',
                                    ],
                                    'body' => [
                                        'type' => 'text',
                                    ],
                                    'keywords' => [
                                        'type' => 'keyword',
                                    ],
                                    'category_id' => [
                                        'type' => 'integer',
                                        'include_in_all' => false,
                                    ],
                                    'tags' => [
                                        'type' => 'integer',
                                        'include_in_all' => false,
                                    ],
                                    'post_date' => [
                                        'type' => 'date',
                                        'format' => 'epoch_second', // timestamp
                                        'include_in_all' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        // ...
    ],
    // ...
];
```
You can configure multiple elasticsearch clients if you need. This could be different search clusters used by your application. Each client can be defined as multiple `hosts`, this parameter contains list of hosts or ip addresses of your elasticsearch servers, optionally followed by a port(default is 9200). You can find more options in [elasticsearch-php documentation](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html). By default connection to either one of these servers is calculated using round-robin strategy.

Consider your needs on performance and durability when selecting the [number of shards and replicas](https://www.elastic.co/guide/en/elasticsearch/reference/5.4/indices-create-index.html). Right amount of these parameters is a compromise between those characteristics. Above example uses the default number of shards and replicas, so if that is what you want, you can omit this parameters in configuration.

After you specify the name of the index and the client using this index, you need to specify the fields that are required for the search. These fields can be grouped into mappings the way you find it logical and convenient to search for(e.g. 'posts'). In terms of full-text search any single piece of data that you add to index called a document. Easy way to understand the meaning of the mapping is to think of it as a document type. By default Elasticsearch don't force you to provide that kind of schema for you data, so if your document has more fields then listed in mapping, those ne fields would be stored as well eithout any problems. To change that behaviour you can set `dynamic` to `strict`, unspecified fields upon insert will raise in an error then.

When you insert new document each field is processed based on it's [datatype](https://www.elastic.co/guide/en/elasticsearch/reference/5.4/mapping-types.html). For example, `text` datatype is needed to index full-text values (these values are analyzed) and `keyword` is searchable by their exact value, which is useful for filtering or aggregations. Please note that if you want to store an array, there is no need to explicitly define `array` datatype, you can store array of intergers in `integer` datatype.

By default Elasticsearch store incoming documents upon indexing, when you retrieve search results for it, you will find the original content in [_source](https://www.elastic.co/search?q=_source&section=Docs%2FElasticsearch%2FReference%2F5.4) field. You can disable that behavior by setting parameter in mapping config:
```
'_source' => [
    'enabled' => true
],
```
From the other hand you can set `'index' => false` in particular mapping field to prevent that field to be indexed. 

Elasticsearch also has a special [_all](https://www.elastic.co/guide/en/elasticsearch/reference/5.4/mapping-all-field.html) field, which concatenates the values of all of the other fields. It's used when you don't specify the list of fields to search in, but it does not get stored.

Extension comes with console command to create and delete indices depending on configuration:
```php
<?php
// config/main.php
return [
    // ...
    'controllerMap' => [
        'elastic-index' => \mikemadisonweb\elasticsearch\commands\IndexController::class,
    ],
    // ...
];
```

Usage
-------------
#### Create index
After you configure your indices there is the command to create them on Elasticsearch:
```bash
php yii elastic-index/create 'my-blog'
```
If you don't provide an index name it will create all defined indices.
#### Indexing Documents
After you have created an index you can insert new records like so:
```php
$indexer = \Yii::$app->elasticsearch->getIndexer('my-blog', 'posts');
$blogPost = [
    'title' => 'New in Elasticsearch 6.0',
    'body' => 'Lots of stuff...',
    'keywords' => 'Elasticsearch',
    'category_id' => '3',
    'tags' => [1, 43, 64],
];
$this->indexer->insert($blogPost);
```
Insert method has a second optional parameter - an unique [id](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-id-field.html). You may use any string you want as an id, every document in the index has that id and if you don't pass it to the method id would be automatically generated by Elasticsearch. 

Other useful methods in the Indexer class:
```php
IndexerResponse update(array $fields, string $id, array $upsert = [], $script = '', array $scriptParams = [])
IndexerResponse delete(string $id, bool $ignoreMissing = false)
array           insertBatch(array $batch)
```
Batch insert is a way to speed up indexing process and it is an only method returning an array instead of ElasticResponse object. An array of documents in the batch can be numerical(without an id) and associative with ids as keys.
#### Searching
Extension provide a builder to simplify the process of query composition. To execute a full-text search on 'title' field you should use `match()` method:
```php
$finder = \Yii::$app->elasticsearch->getFinder('my-blog', 'posts');
$results = $finder
    ->match('How to use Elasticsearch', 'title')
    ->all();
```
There are also ways to filter the results, sort them or select specific portion of it:
```php
$finder = \Yii::$app->elasticsearch->getFinder('my-blog', 'posts');
$results = $finder
    ->match('How to use Elasticsearch', 'title')
    ->where('category_id = 14')
    ->sort('post_date:desc)
    ->limit(100)
    ->offset(100)
    ->all();
    
foreach ($results as $result) {
    // ...
}
```
Please note that if you use analyzed filed(full-text field) in `where()` method you will probably get wrong results, because that method should be used only for non-analyzed datatypes(keyword, integer, boolean etc). There are optional parameters in `match()` method that you most certainly should keep eye on:
```
Finder match(string $query, array|string $fields = '_all', string $condition = 'and', string $operator = 'and', string $type = 'cross_fields')
```
Most of the times you would need to match query string to particular fields, but if you want to search different strings on different fields (e.g. 'some text' on 'title' and/or 'another data' on 'post') then you can choose logical operator in `condition` parameter for your needs. If you searching on multiple fields you can define `operator` and `type` parameters to specify the [logic for searching on these fields](https://www.elastic.co/guide/en/elasticsearch/reference/5.4/query-dsl-multi-match-query.html).

More complex filter causes can be specified in a SQL-like fashion:
```php
$finder = \Yii::$app->elasticsearch->getFinder('my-blog', 'posts');
$results = $finder
    ->where("category_id = 11 OR (tags in [1, 53, 78] AND keywords = 'Elastica')")
    ->all();
```
If by any chance you feel limited to Finder methods you can pass raw JSON and interact directly with Elasticsearch API:
```php
$json = '{
    "query" : {
        "match" : {
            "post" : "Most important things in life"
        }
    }
}';
$finder = \Yii::$app->elasticsearch->getFinder('my-blog', 'posts');
$results = $finder->sendJson($json);
```
Analysis
-------------
There is a huge amount of options Elasticearch provides for analyzing text. Alalyzers, normalizers, token filters are out of the scope of this documentation, you can find loads of information about them in [official docs](https://www.elastic.co/guide/en/elasticsearch/reference/5.4/analysis.html).

This is an example analysis config for russian language with stop-words filter and snowball stemmer:
```php
<?php
// config/main.php
return [
    // index settings
    'settings' => [
        'number_of_shards' => 1,
        'number_of_replicas' => 0,
        'analysis' => [
            'filter' => [
                'russian_stop' => [
                    'type' => 'stop',
                    'stopwords' => '_russian_',
                ],
                'russian_stemmer' => [
                    'type' => 'stemmer',
                    'language' => 'russian',
                ],
            ],
            'analyzer' => [
                'default' => [
                    'tokenizer' => 'standard',
                    'filter' => [
                        'lowercase',
                        'russian_stop',
                        'russian_stemmer',
                    ],
                ],
            ],
        ],
    ],
    // ...
];
```
Highlighting 
-------------
Elasticsearch provide a way to highlight search terms in source text. If you search for 'New possibilities' the word 'possibility' found in some document would be surrounded with tags like so `<em>possibility</em>`. These highligted results would be in the 'highlight' field of the response, not in the '_source' field.
 To turn on this functionality you should enable it in index configuration. Default sort order and limit options can be configured there as well:
```php
<?php
// config/main.php
return [
    // index configuration
    'index' => 'my-blog',
    'defaults' => [
        'limit' => 100,
        'sort' => 'post_date:desc',
        'highlight' => [
            'enabled' => true,
            'pre_tags' => '<span class=“highlight”>',
            'post_tags' => '</span>',
            'fields' => ['*' => ['number_of_fragments' => 0]]
        ],
    ],
    // ...
];
```
Asterisk sign means it would highlight in all configured analyzed fields, but there is a catch. If you want to receive highlighted results in all text fields, then you shouldn't pass `_all` as field to the `match()` function.