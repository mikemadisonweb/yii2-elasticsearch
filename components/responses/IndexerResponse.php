<?php

namespace mikemadisonweb\elasticsearch\components\responses;

/**
 * Class IndexerResponse
 */
class IndexerResponse extends ElasticResponse
{
    public $result;

    protected $index;
    protected $type;
    protected $id;
    protected $version;

    /**
     * FinderResponse constructor.
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->result = $response['result'];
        $this->index = $response['_index'];
        $this->type = $response['_type'];
        $this->id = $response['_id'];
        $this->version = $response['_version'];

        parent::__construct($response);
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return boolean
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }
}
