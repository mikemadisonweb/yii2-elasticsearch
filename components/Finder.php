<?php

namespace mikemadisonweb\elasticsearch\components;

use mikemadisonweb\elasticsearch\components\builders\BoolBuilder;
use mikemadisonweb\elasticsearch\components\conditions\ConditionParser;
use mikemadisonweb\elasticsearch\components\queries\BoolQuery;
use mikemadisonweb\elasticsearch\components\queries\QueryInterface;
use mikemadisonweb\elasticsearch\components\responses\FinderResponse;
use Elasticsearch\Client;
use mikemadisonweb\elasticsearch\components\responses\IndexerResponse;
use yii\base\InvalidConfigException;

class Finder
{
    protected $index;
    protected $params;
    protected $client;

    /**
     * @var QueryInterface
     */
    protected $query;
    protected $mapping;
    /**
     * @var DefaultsResolver
     */
    protected $defaultsResolver;

    public function __construct($index, $mapping, Client $client)
    {
        if (!isset($index['index'])) {
            throw new InvalidConfigException('Index name is not configured.');
        }
        $this->index = $index;
        $this->mapping = $mapping;
        $this->client = $client;
        // default
        $this->conditionBuilder = new BoolBuilder(new ConditionParser());
        if (isset($this->index['defaults'])) {
            $this->defaultsResolver = new DefaultsResolver($this->index['defaults']);
        }
        $this->reset();
    }

    /**
     * @param QueryInterface $query
     */
    public function setQuery(QueryInterface $query)
    {
        $this->query = $query;
    }

    /**
     * @return QueryInterface
     */
    public function getQuery()
    {
        return $this->query;
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
     * @param $query
     * @param string $fields
     * @param string $condition
     * @param string $operator
     * @param string $type
     * @return $this
     * @throws \Exception
     */
    public function match($query, $fields = '_all', $condition = 'and', $operator = 'and', $type = 'cross_fields')
    {
        switch ($condition) {
            case 'and':
                $condition = 'must';
                break;
            case 'or':
                $condition = 'should';
                break;
            default:
                throw new \Exception('You should pass one of the following conditions: `or`, `and`');
        }

        if (is_array($fields)) {
            if (count($fields) > 1) {
                $this->query->appendParam($condition, [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => $fields,
                        'type' => $type,
                        'operator' => $operator,
                    ],
                ]);
            } elseif (count($fields) === 1) {
                $fields = current($fields);
            }
        }
        if (is_string($fields)) {
            $this->query->appendParam($condition, [
                'match' => [
                    $fields => [
                        'query' => $query,
                        'operator' => $operator,
                    ],
                ],
            ]);
        }

        return $this;
    }

    /**
     * @param string $expression
     * @return $this
     */
    public function where($expression)
    {
        $this->query = $this->conditionBuilder->buildQuery($this->query, $expression);
        $this->query->setParam('minimum_should_match', '0<1');

        return $this;
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
        $this->reset();

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
        $this->reset();

        return $response;
    }

    /**
     * @return FinderResponse
     */
    public function all()
    {
        if (!isset($this->params['body']['query'])) {
            $this->params['body']['query'] = $this->query->build();
        }

        $this->params = $this->defaultsResolver->resolve($this->params);
        $response = $this->client->search($this->params);
        $this->reset();

        return new FinderResponse($response);
    }

    /**
     * @return array
     */
    public function count()
    {
        if (!isset($this->params['body']['query'])) {
            $this->params['body']['query'] = $this->query->build();
        }

        unset($this->params['size']);
        unset($this->params['sort']);
        $response = $this->client->count($this->params);
        $this->reset();

        return $response['count'];
    }

    /**
     * @param $json
     * @return FinderResponse
     */
    public function sendJson($json)
    {
        $this->params['body'] = $json;
        $response = $this->client->search($this->params);

        return new FinderResponse($response);
    }

    /**
     * Reset all query parameters to build next request
     */
    public function reset()
    {
        $this->params = [
            'index' => $this->index['index'],
            'type' => $this->mapping,
            'body' => null,
        ];
        if (null === $this->query) {
            $this->query = new BoolQuery();
        } else {
            $this->query->reset();
        }
    }
}
