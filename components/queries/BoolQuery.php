<?php

namespace mikemadisonweb\elasticsearch\components\queries;

class BoolQuery extends Query
{
    const QUERY_NAME = 'bool';

    protected $queryName = self::QUERY_NAME;

    /**
     * @return array
     */
    public function getAllowedKeys()
    {
        return ['must', 'filter', 'must_not', 'should', 'minimum_should_match', 'boost'];
    }

    /**
     * Only one param should be in query (these params should be set at the end of query building)
     * @param $name
     * @param $value
     * @throws \Exception
     */
    public function appendParam($name, $value)
    {
        parent::appendParam($name, $value);
    }
}
