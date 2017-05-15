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
}
