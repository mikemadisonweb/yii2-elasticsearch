<?php

namespace mikemadisonweb\elasticsearch\components;

use yii\base\InvalidConfigException;

/**
 * Class DefaultsResolver
 * @package mikemadisonweb\elasticsearch\components
 */
class DefaultsResolver
{
    protected $limit;
    protected $sort;
    protected $highlight;

    protected $allowed = [
        'limit' => 'is_numeric',
        'sort' => 'is_string',
        'highlight' => 'is_array',
    ];

    public function __construct(array $defaultsConfig)
    {
        foreach ($defaultsConfig as $key => $value) {
            if (!isset($this->allowed[$key])) {
                throw new InvalidConfigException("`{$key}` option is not allowed in `defaults` section.");
            }
            if (!call_user_func_array($this->allowed[$key], [$value])) {
                throw new InvalidConfigException("`{$key}` value type is not allowed. Applied check: {$this->allowed[$key]}");
            }

            $this->$key = $value;
        }
    }

    public function resolve(array $params)
    {
        if (!isset($params['sort']) && null !== $this->sort) {
            $params['sort'] = $this->sort;
        }

        if (!isset($params['size']) && null !== $this->limit) {
            $params['size'] = $this->limit;
        }

        if (!isset($params['body']['highlight']) && isset($this->highlight)) {
            if (isset($this->highlight['enabled']) && $this->highlight['enabled']) {
                if (isset($this->highlight['fields'])) {
                    $params['body']['highlight']['fields'] = $this->highlight['fields'];
                } else {
                    $params['body']['highlight']['fields'] = ['*' => ['number_of_fragments' => 0]];
                }

                if (isset($this->highlight['pre_tags'])) {
                    $params['body']['highlight']['pre_tags'] = $this->highlight['pre_tags'];
                }

                if (isset($this->highlight['post_tags'])) {
                    $params['body']['highlight']['post_tags'] = $this->highlight['post_tags'];
                }
            }
        }

        return $params;
    }
}
