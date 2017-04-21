<?php

namespace mikemadisonweb\elasticsearch\components;

class Query implements QueryInterface
{
    protected $params = [];

    /**
     * @return array
     */
    public function build()
    {
        $query = array_filter($this->params);
        if (empty($query)) {
            $query['match_all'] = new \stdClass();
        }

        return $query;
    }

    public function reset()
    {
        $this->params = [];
    }

    protected function rules()
    {
        return [
            'term' => [$this, 'setValue'],
            'terms' => [$this, 'setValue'],
            'bool' => [$this, 'appendBool'],
            'match' => [$this, 'setValue'],
            'multi_match' => [$this, 'setValue'],
            'match_all' => [$this, 'setExclusive'],
        ];
    }

    /**
     * @param $paramName
     * @param $value
     * @throws \Exception
     */
    public function setParam($paramName, $value)
    {
        if (!isset($this->rules()[$paramName])) {
            throw new \Exception("Parameter {$paramName} is not defined in Query class");
        }
        $this->applyCallbacks($paramName, $value);
    }

    /**
     * @param $paramName
     * @param $value
     * @throws \Exception
     */
    protected function applyCallbacks($paramName, $value)
    {
        $validationClosures = $this->rules()[$paramName];
        if (is_callable($validationClosures)) {
            call_user_func_array($validationClosures, [$paramName, $value]);
        } else {
            foreach ($validationClosures as $validationClosure) {
                call_user_func_array($validationClosure, [$paramName, $value]);
            }
        }
    }

    /**
     * Set ordinary parameter
     * @param $paramName
     * @param $value
     */
    protected function setValue($paramName, $value)
    {
        $this->params[$paramName] = $value;
    }

    /**
     * Only one param should be in query (these params should be set at the end of query building)
     * @param $paramName
     * @param $value
     * @throws \Exception
     */
    protected function setExclusive($paramName, $value)
    {
        if (!empty($this->params)) {
            throw new \Exception("`{$paramName}` queries should not have additional query parameters.");
        }

        $this->params[$paramName] = $value;
    }

    /**
     * Only one param should be in query (these params should be set at the end of query building)
     * @param $paramName
     * @param $value
     * @throws \Exception
     */
    protected function appendBool($paramName, array $value)
    {
        $allowedKeys = ['must', 'filter', 'must_not', 'should', 'minimum_should_match', 'boost'];
        if (count($value) > 1) {
            throw new \Exception('You should append bool query parameters one by one');
        }
        $operand = key($value);
        if (!in_array($operand, $allowedKeys)) {
            $allowedKeys = json_encode($allowedKeys);
            throw new \Exception("List of allowed operands in bool query: {$allowedKeys} Given: {$operand}");
        }

        $this->params[$paramName][$operand][] = $value[$operand];
    }
}
