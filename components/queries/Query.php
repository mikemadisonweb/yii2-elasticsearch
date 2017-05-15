<?php

namespace mikemadisonweb\elasticsearch\components\queries;

class Query implements QueryInterface
{
    const MATCH_ALL_QUERY = 'match_all';

    protected $queryName;
    protected $params = [];

    /**
     * @return array
     */
    public function getAllowedKeys()
    {
        return ['boost'];
    }

    /**
     * @param $name
     * @param $value
     */
    public function setParam($name, $value)
    {
        $this->validate($name);
        $this->params[$name] = $value;
    }

    /**
     * @param $name
     * @param $value
     */
    public function appendParam($name, $value)
    {
        $this->validate($name);
        if (is_array($value) && count($value) > 1) {
            foreach ($value as $key => $item) {
                $this->params[$name][] = $item;
            }
        } else {
            $this->params[$name][] = $value;
        }
    }

    /**
     * @return array
     */
    public function build()
    {
        $params = array_filter($this->params);
        if (empty($params) || !isset($this->queryName)) {
            $query[self::MATCH_ALL_QUERY] = new \stdClass();
        } else {
            $query[$this->queryName] = $params;
        }

        return $query;
    }

    public function reset()
    {
        $this->params = [];
    }

    protected function validate($paramName)
    {
        $allowed = $this->getAllowedKeys();
        if (!in_array($paramName, $allowed)) {
            $allowed = json_encode($allowed);
            throw new \Exception("List of allowed parameters in query: {$allowed} Given: {$paramName}");
        }
    }
}
