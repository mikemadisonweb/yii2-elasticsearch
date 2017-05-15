<?php

namespace mikemadisonweb\elasticsearch\components\queries;

interface QueryInterface
{
    /**
     * @return array
     */
    public function getAllowedKeys();

    /**
     * @param $paramName
     * @param $value
     */
    public function setParam($paramName, $value);

    /**
     * @param $name
     * @param $value
     */
    public function appendParam($name, $value);

    /**
     * @return array
     */
    public function build();

    public function reset();
}
