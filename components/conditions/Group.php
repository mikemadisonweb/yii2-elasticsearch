<?php

namespace mikemadisonweb\elasticsearch\components\conditions;

class Group extends ConditionItem
{
    protected $condition;
    protected $items = [];

    protected $conditionMapping = [
        ConditionParser::AND_CONDITION_TOKEN => ['must', 'must_not'],
        ConditionParser::OR_CONDITION_TOKEN => ['should', ''],
    ];

    protected $inverseLogicMapping = [
        ConditionParser::IN_OPERATOR_TOKEN => 0,
        ConditionParser::NOT_IN_OPERATOR_TOKEN => 1,
        ConditionParser::EQUAL_OPERATOR_TOKEN => 0,
        ConditionParser::NON_EQUAL_OPERATOR_TOKEN => 1,
        ConditionParser::GT_OPERATOR => 0,
        ConditionParser::GTE_OPERATOR => 0,
        ConditionParser::LT_OPERATOR => 0,
        ConditionParser::LTE_OPERATOR => 0,
    ];

    /**
     * You can add an Expression or another logical Group
     * @param ConditionItem $item
     */
    public function addItem(ConditionItem $item)
    {
        $this->items[] = $item;
    }

    /**
     * @param $logicalOperator
     * @throws \Exception
     */
    public function setCondition($logicalOperator)
    {
        $allowed = $this->getAllowedConditions();
        if (!in_array($logicalOperator, $allowed)) {
            $all = json_encode($allowed);
            throw new \Exception("Group should have one of allowed conditions: {$all}");
        }
        if ($logicalOperator !== $this->condition && null !== $this->condition) {
            throw new \LogicException('Condition parsing error: `and` mixed with `or` conditions does not allowed without parenthesis');
        }
        if (empty($this->items)) {
            throw new \LogicException("Condition parsing error: First operand is missing for {$logicalOperator}");
        }

        $this->condition = $this->determineCondition($logicalOperator);
    }

    /**
     * @param $operator
     * @return string
     */
    protected function determineCondition($operator)
    {
        $firstItem = current($this->items);
        // In case of 'NOT ...' type of queries
        if ($firstItem instanceof Expression) {
            $condition = $this->conditionMapping[$operator][$this->inverseLogicMapping[$firstItem->getOperator()]];
        } else {
            $condition = $this->conditionMapping[$operator][0];
        }

        if (!$condition) {
            throw new \LogicException('No `should_not` query defined in Elasticsearch native API. Please, make sure that `OR NOT` query makes sense for you.');
        }

        return $condition;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return mixed
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return array
     */
    protected function getAllowedConditions()
    {
        return [
            ConditionParser::AND_CONDITION_TOKEN,
            ConditionParser::OR_CONDITION_TOKEN,
        ];
    }
}
