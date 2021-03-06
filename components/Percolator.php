<?php

namespace mikemadisonweb\elasticsearch\components;

use mikemadisonweb\elasticsearch\components\queries\QueryInterface;
use mikemadisonweb\elasticsearch\components\responses\FinderResponse;
use mikemadisonweb\elasticsearch\components\responses\IndexerResponse;
use Elasticsearch\Client;
use Elasticsearch\Serializers\SerializerInterface;
use Elasticsearch\Serializers\SmartSerializer;
use yii\base\InvalidConfigException;

/**
 * todo() Make possible to change percolator field name
 * Class Percolator
 * @package mikemadisonweb\elasticsearch\components
 */
class Percolator
{
    protected $index;
    protected $params;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Client
     */
    protected $client;
    protected $mapping;

    /**
     * Percolator constructor.
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
        $this->resetParams();
        $this->client   = $client;
        // default
        $this->serializer = new SmartSerializer();
    }

    /**
     * @param SerializerInterface $serializer
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * @param QueryInterface $query
     * @param string $id
     * @return IndexerResponse
     * @throws \Exception
     */
    public function insert(QueryInterface $query, $id = '')
    {
        if (empty($query)) {
            $passedId = $id ? " Percolator id: {$id}" : '';
            throw new \Exception("You are trying to insert empty percolate query.{$passedId}");
        }
        if ('' !== $id) {
            $this->params['id'] = $id;
        }
        $jsonQuery = $this->serializer->serialize(['query' => $query->build()]);
        $this->params['body'] = $jsonQuery;
        $response = $this->client->index($this->params);
        $this->resetParams();

        return new IndexerResponse($response);
    }

    /**
     * $this->client->percolate() is using old deprecated percolator functionality
     * This method using new percolate query
     * @param $documentId
     * @param $documentType
     * @return FinderResponse
     */
    public function percolate($documentId, $documentType)
    {
        $this->params['body']['query'] = [
            'percolate' => [
                'field' => 'query',
                'document_type' => $documentType,
                'index' => $this->index['index'],
                'type' => $documentType,
                'id' => $documentId,
            ],
        ];

        $response = $this->client->search($this->params);
        $this->resetParams();

        return new FinderResponse($response);
    }

    /**
     * @param array $fields
     * @param $mappingType
     * @return FinderResponse
     */
    public function percolateNonExisting(array $fields, $mappingType)
    {
        $this->params['body']['query']['percolate'] = [
            'field' => 'query',
            'document_type' => $mappingType,
            'document' => $fields,
        ];

        $response = $this->client->search($this->params);
        $this->resetParams();

        return new FinderResponse($response);
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
