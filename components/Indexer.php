<?php

namespace mikemadisonweb\elasticsearch\components;

use Elasticsearch\Client;
use yii\base\InvalidConfigException;
use yii\db\Exception;

class Indexer
{
    protected $index;
    protected $client;
    protected $params;
    protected $mapping;

    /**
     * Indexer constructor.
     * @param $index
     * @param $mapping
     * @param Client $client
     * @throws InvalidConfigException
     */
    public function __construct($index, $mapping, Client $client)
    {
        if (!isset($index['index'])) {
            throw new InvalidConfigException('Index name is not configured.');
        }
        $this->index    = $index;
        $this->mapping = $mapping;
        $this->params = [
            'index' => $this->index['index'],
            'type' => $mapping,
            'body' => null,
        ];
        $this->client   = $client;
    }


    /**
     * @param array $fields
     * @param string $id
     * @return array
     * @throws \Exception
     */
    public function insert(array $fields, $id = '')
    {
        if (empty($fields)) {
            $passedId = $id ? " Document id: {$id}" : '';
            throw new \Exception("You are trying to insert empty document.{$passedId}");
        }

        $this->params['body'] = $fields;

        if ('' !== $id) {
            $this->params['id'] = $id;
        }

        return $this->client->index($this->params);
    }

    /**
     * @param array $fields
     * @param $id
     * @param array $upsert
     * @param string $script
     * @param array $scriptParams
     * @return array
     * @throws \Exception
     */
    public function update(array $fields, $id, array $upsert = [], $script = '', array $scriptParams = [])
    {
        if (!$id) {
            throw new \Exception('You should pass document id in order to update.');
        }
        $body['doc'] = $fields;
        if (!empty($upsert)) {
            $body['upsert'] = $upsert;
        }
        if ('' !== $script) {
            $body['script'] = $script;
            $body['params'] = $scriptParams;
        }

        $this->params['id'] = $id;
        $this->params['body'] = $body;

        return $this->client->update($this->params);
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function delete($id)
    {
        if (!$id) {
            throw new \Exception('You should pass document id in order to delete.');
        }
        unset($this->params['body']);
        $this->params['id'] = $id;

        return $this->client->delete($this->params);
    }

    /**
     * @param array $batch
     * @return array
     */
    public function insertBatch(array $batch)
    {
        $params = [];
        $isAssoc = $this->isArrayAssoc($batch);
        foreach ($batch as $id => $document) {
            if ($isAssoc) {
                $metadata = [
                    'index' => [
                        '_index' => $this->index['index'],
                        '_type' => $this->mapping,
                        '_id' => $id,
                    ],
                ];
            } else {
                $metadata = [
                    'index' => [
                        '_index' => $this->index['index'],
                        '_type' => $this->mapping,
                    ],
                ];
            }
            $params['body'][] = $metadata;
            $params['body'][] = $document;
        }
        $response = $this->client->bulk($params);

        if ($response['errors']) {
            foreach ($response['items'] as $item) {
                if (isset($item['index']['error'])) {
                    \Yii::$app->elasticsearch->trigger(ElasticErrorEvent::BULK_ERRORS, new ElasticErrorEvent([
                        'indexName' => $this->index,
                        'documentId' => $item['index']['_id'],
                        'error' => $item['index']['error'],
                        'client' => $this->client,
                    ]));
                }
            }
        }

        return $response;
    }

    /**
     * Check whether an array is associative or numeric
     * @param array $arr
     * @return bool
     */
    private function isArrayAssoc(array $arr)
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
