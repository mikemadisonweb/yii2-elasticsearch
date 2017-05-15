<?php

namespace mikemadisonweb\elasticsearch\components\normalizers;

interface NormalizerInterface
{

    /**
     * Подготовка сущности к передаче по API
     * @param array $entities
     * @return array
     */
    public function normalize(array $entities) : array;
}
