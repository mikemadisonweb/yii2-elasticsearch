<?php

namespace mikemadisonweb\elasticsearch\components;

/**
 * Class ElasticResponse
 */
class ElasticResponse implements \Iterator
{
    public $executionTime;
    public $isTimedOut;
    public $total;
    public $maxScore;

    protected $hits;
    protected $shardInfo;

    /**
     * ElasticResponse constructor.
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->executionTime = $response['took'];
        $this->isTimedOut = $response['timed_out'];
        $this->shardInfo = $response['_shards'];
        $this->hits = $response['hits']['hits'];
        $this->total = $response['hits']['total'];
        $this->maxScore = $response['hits']['max_score'];
    }

    /**
     * @return array
     */
    public function getShardInfo()
    {
        return $this->shardInfo;
    }

    public function rewind()
    {
        reset($this->hits);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->hits);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->hits);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->hits);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $key = key($this->hits);

        return ($key !== null && $key !== false);
    }
}
