<?php

namespace mikemadisonweb\elasticsearch\components\responses;

/**
 * Class FinderResponse
 */
class FinderResponse extends ElasticResponse implements \Iterator
{
    public $executionTime;
    public $isTimedOut;
    public $total;
    public $maxScore;

    protected $hits;
    protected $aggregations;

    /**
     * FinderResponse constructor.
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->executionTime = $response['took'];
        $this->isTimedOut = $response['timed_out'];
        $this->hits = $response['hits']['hits'];
        $this->aggregations = $response['aggregations'];
        $this->total = $response['hits']['total'];
        $this->maxScore = $response['hits']['max_score'];

        parent::__construct($response);
    }

    /**
     * @return array
     */
    public function getHits()
    {
        return $this->hits;
    }
    
    /**
     * @return array
     */
    public function getAggregations()
    {
        return $this->aggregations;
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
