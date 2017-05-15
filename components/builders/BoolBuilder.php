<?php

namespace mikemadisonweb\elasticsearch\components\builders;

use mikemadisonweb\elasticsearch\components\conditions\ConditionParser;
use mikemadisonweb\elasticsearch\components\conditions\Expression;
use mikemadisonweb\elasticsearch\components\conditions\Group;
use mikemadisonweb\elasticsearch\components\queries\BoolQuery;
use mikemadisonweb\elasticsearch\components\queries\QueryInterface;

class BoolBuilder
{

    /**
     * ConditionBuilder constructor.
     * @param ConditionParser $parser
     */
    public function __construct(ConditionParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param QueryInterface $query
     * @param string $conditionString
     * @return QueryInterface
     * @throws \Exception
     */
    public function buildQuery(QueryInterface $query, $conditionString)
    {
        $parsedCondition = $this->parser->parse($conditionString);
        if (null === $parsedCondition->getCondition()) {
            if (count($parsedCondition->getItems()) > 1) {
                throw new \Exception('Condition group constructed the wrong way, you need to pass a logical operator.');
            }
            $parsedCondition->setCondition(ConditionParser::AND_CONDITION_TOKEN);
        }

        $conditionParams = $this->build($parsedCondition);
        foreach ($conditionParams as $keyword => $queryParams) {
            foreach ($queryParams as $param) {
                $query->appendParam($keyword, $param);
            }
        }

        return $query;
    }

    /**
     * @param Group $group
     * @return array
     */
    protected function build(Group $group)
    {
        $params = [];
        $items = $group->getItems();
        foreach ($items as $item) {
            if ($item instanceof Expression) {
                $params[$group->getCondition()][] = $this->buildExpression($item);
            } elseif ($item instanceof Group) {
                $params[$group->getCondition()][] = [
                    BoolQuery::QUERY_NAME => $this->build($item),
                ];
            }
        }

        return $params;
    }

    /**
     * @param Expression $expression
     * @return mixed
     */
    protected function buildExpression(Expression $expression)
    {
        $keyword = $expression->getKeyword();
        $field = $expression->getProperty();
        $value = $expression->getValue();
        if ('range' === $keyword) {
            $rangeOperator = $expression->getRangeOperator();
            $params = [
                $keyword => [
                    $field => [
                        $rangeOperator => $value,
                    ],
                ],
            ];
        } else {
            $params = [
                $keyword => [
                    $field => $value,
                ],
            ];
        }

        return $params;
    }
}
