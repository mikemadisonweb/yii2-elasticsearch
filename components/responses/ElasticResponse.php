<?php

namespace mikemadisonweb\elasticsearch\components\responses;

/**
 * Class ElasticResponse
 */
abstract class ElasticResponse
{
    protected $shardInfo;

    /**
     * FinderResponse constructor.
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->shardInfo = $response['_shards'];
    }

    /**
     * @return array
     */
    public function getShardInfo()
    {
        return $this->shardInfo;
    }
    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return !$this->getShardInfo()['failed'];
    }
}
