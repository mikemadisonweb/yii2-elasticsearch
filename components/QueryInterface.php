<?php

namespace mikemadisonweb\elasticsearch\components;

interface QueryInterface
{
    /**
     * @param $paramName
     * @param $value
     */
    public function setParam($paramName, $value);

    /**
     * @return array
     */
    public function build();

    public function reset();
}
