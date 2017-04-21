<?php

namespace mikemadisonweb\elasticsearch\components;

use Elasticsearch\Client;
use yii\base\InvalidConfigException;

class Finder
{
    protected $index;
    protected $params;
    protected $client;

    public function __construct($index, $mapping, Client $client, QueryInterface $query)
    {
        if (!isset($index['index'])) {
            throw new InvalidConfigException('Index name is not configured.');
        }
        $this->index    = $index;
        $this->params = [
            'index' => $this->index['index'],
            'type' => $mapping,
            'body' => null,
        ];
        $this->client   = $client;
        $this->query = $query;
    }

    /**
     * @param $include
     * @return $this
     * @throws \Exception
     */
    public function select($include)
    {
        if (is_string($include) || is_bool($include) || is_array($include)) {
            $this->params['_source'] = $include;
        } else {
            throw new \Exception('Value passed to `select` method should be either string, array or boolean');
        }

        return $this;
    }

    /**
     * @param $limit
     * @return $this
     * @throws \Exception
     */
    public function limit($limit)
    {
        if (is_numeric($limit)) {
            $this->params['size'] = $limit;
        } else {
            throw new \Exception('Value passed to `limit` method should be numeric');
        }

        return $this;
    }

    /**
     * @param $offset
     * @return $this
     * @throws \Exception
     */
    public function offset($offset)
    {
        if (is_numeric($offset)) {
            $this->params['from'] = $offset;
        } else {
            throw new \Exception('Value passed to `offset` method should be numeric');
        }

        return $this;
    }

    /**
     * @param $fieldName
     * @return $this
     * @throws \Exception
     */
    public function sort($fieldName)
    {
        if (is_string($fieldName)) {
            $this->params['sort'] = $fieldName;
        } else {
            throw new \Exception('Value passed to `sort` method should be a string');
        }

        return $this;
    }

    /**
     * Full-text searches
     * @param $fields
     * @param $query
     * @return $this
     * @throws \Exception
     */
    public function match($query, $fields = '_all')
    {
        if (is_array($fields)) {
            if (count($fields) > 1) {
                $this->query->setParam('multi_match', [
                    'query' => $query,
                    'fields' => $fields,
                ]);
            } elseif (count($fields) === 1) {
                $fields = current($fields);
            }
        }
        if (is_string($fields)) {
            $this->query->setParam('match', [
                $fields => $query,
            ]);
        }

        return $this;
    }

    /**
     * todo() Add query SQL-like syntax string parsing (nested logic not supported right now)
     * @param $field
     * @param $operator
     * @param $value
     * @return $this
     * @throws \Exception
     */
    public function andWhere($field, $operator, $value)
    {
        $operator = strtolower($operator);
        if (!is_string($field)) {
            $field = json_encode($field);
            throw new \Exception("Field name should be a string, given: {$field}");
        }
        if (is_array($value)) {
            if ('in' !== $operator) {
                throw new \Exception('Where clause misconfigured. Array values allowed only with `IN` operator');
            }
            $this->query->setParam('bool', [
                'must' => [
                    'terms' => [
                        $field => $value,
                    ],
                ],
            ]);
        }
        switch ($operator) {
            case '=':
                $this->query->setParam('bool', [
                    'must' => [
                        'term' => [
                            $field => $value,
                        ],
                    ],
                ]);
                break;
            case '!=':
                $this->query->setParam('bool', [
                    'must_not' => [
                        'term' => [
                            $field => $value,
                        ],
                    ],
                ]);
                break;
            case 'gt':
            case 'gte':
            case 'lt':
            case 'lte':
                $this->query->setParam('bool', [
                    'must' => [
                        'range' => [
                            $field => [
                                $operator => $value,
                            ],
                        ],
                    ],
                ]);
                break;
        }

        return $this;
    }

    public function orWhere($field, $operator, $value)
    {
        $operator = strtolower($operator);
        if (!is_string($field)) {
            $field = json_encode($field);
            throw new \Exception("Field name should be a string, given: {$field}");
        }
        if (is_array($value)) {
            if ('in' !== $operator) {
                throw new \Exception('Where clause misconfigured. Array values allowed only with `IN` operator');
            }
            $this->query->setParam('bool', [
                'should' => [
                    'terms' => [
                        $field => $value,
                    ],
                ],
            ]);
        }
        switch ($operator) {
            case '=':
                $this->query->setParam('bool', [
                    'should' => [
                        'term' => [
                            $field => $value,
                        ],
                    ],
                ]);
                break;
            case '!=':
                $this->query->setParam('bool', [
                    'must_not' => [
                        'term' => [
                            $field => $value,
                        ],
                    ],
                ]);
                break;
            case 'gt':
            case 'gte':
            case 'lt':
            case 'lte':
                $this->query->setParam('bool', [
                    'should' => [
                        'range' => [
                            $field => [
                                $operator => $value,
                            ],
                        ],
                    ],
                ]);
                break;
        }

        return $this;
    }

    /**
     * @param $query
     */
    public function query($query)
    {
        $this->params['body']['query'] = $query;
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function get($id)
    {
        unset($this->params['body']);
        $this->params['id'] = $id;
        $response = $this->client->get($this->params);
        $this->query->reset();

        return $response['_source'];
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function exists($id)
    {
        unset($this->params['body']);
        $this->params['id'] = $id;
        $response = $this->client->exists($this->params);
        $this->query->reset();

        return $response;
    }

    /**
     * @return ElasticResponse
     */
    public function all()
    {
        if (!isset($this->params['body']['query'])) {
            $query = $this->query->build();
            if (empty($query)) {
                $query['match_all'] = ['boost' => 1.0];
            }
            $this->params['body']['query'] = $query;
        }

        $this->applyDefaults();
        $response = $this->client->search($this->params);
        $this->query->reset();

        return new ElasticResponse($response);
    }

    /**
     * @return array
     */
    public function count()
    {
        if (!isset($this->params['body']['query'])) {
            $query = $this->query->build();
            if (empty($query)) {
                $query['match_all'] = ['boost' => 1.0];
            }
            $this->params['body']['query'] = $query;
        }

        unset($this->params['size']);
        unset($this->params['sort']);
        $response = $this->client->count($this->params);
        $this->query->reset();

        return $response['count'];
    }

    /**
     * @param $json
     * @return array
     */
    public function sendJson($json)
    {
        $this->params['body'] = $json;

        return $this->client->search($this->params);
    }

    /**
     * todo Take this mess to Defaults class and validate configuration
     */
    private function applyDefaults()
    {
        if (!isset($this->params['sort']) && isset($this->index['defaults']['sort'])) {
            $this->params['sort'] = $this->index['defaults']['sort'];
        }

        if (!isset($this->params['size']) && isset($this->index['defaults']['limit'])) {
            $this->params['size'] = $this->index['defaults']['limit'];
        }

        if (!isset($this->params['body']['highlight']) && isset($this->index['defaults']['highlight'])) {
            if (isset($this->index['defaults']['highlight']['enabled']) && $this->index['defaults']['highlight']['enabled']) {
                // $this->params['_source'] = false;

                if (isset($this->index['defaults']['highlight']['fields'])) {
                    $this->params['body']['highlight']['fields'] = $this->index['defaults']['highlight']['fields'];
                } else {
                    $this->params['body']['highlight']['fields'] = ['*' => ['number_of_fragments' => 0]];
                }

                if (isset($this->index['defaults']['highlight']['pre_tags'])) {
                    $this->params['body']['highlight']['pre_tags'] = $this->index['defaults']['highlight']['pre_tags'];
                }

                if (isset($this->index['defaults']['highlight']['post_tags'])) {
                    $this->params['body']['highlight']['post_tags'] = $this->index['defaults']['highlight']['post_tags'];
                }
            }
        }
    }
}
