<?php

namespace mikemadisonweb\elasticsearch\components;

use mikemadisonweb\elasticsearch\components\events\ElasticErrorEvent;
use mikemadisonweb\elasticsearch\components\responses\IndexerResponse;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use yii\base\InvalidConfigException;

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
        $this->index = $index;
        $this->mapping = $mapping;
        $this->resetParams();
        $this->client = $client;
    }


    /**
     * @param array $fields
     * @param string $id
     * @return IndexerResponse
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
        $response = $this->client->index($this->params);
        $this->resetParams();

        return new IndexerResponse($response);
    }

    /**
     * @param array $fields
     * @param $id
     * @param array $upsert
     * @param string $script
     * @param array $scriptParams
     * @return IndexerResponse
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
        $response = $this->client->update($this->params);
        $this->resetParams();

        return new IndexerResponse($response);
    }

    /**
     * @param $id
     * @param bool $ignoreMissing
     * @return IndexerResponse|bool
     * @throws \Exception
     */
    public function delete($id, $ignoreMissing = false)
    {
        if (!$id) {
            throw new \Exception('You should pass document id in order to delete.');
        }
        unset($this->params['body']);
        $this->params['id'] = $id;
        try {
            $response = $this->client->delete($this->params);
        } catch (Missing404Exception $e) {
            if (!$ignoreMissing) {
                throw new Missing404Exception($e->getMessage());
            }

            return false;
        }

        $this->resetParams();

        return new IndexerResponse($response);
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

        $this->resetParams();

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

    protected function resetParams()
    {
        $this->params = [
            'index' => $this->index['index'],
            'type' => $this->mapping,
            'body' => null,
        ];
    }
}
