<?php

namespace mikemadisonweb\elasticsearch\components\conditions;

class Expression extends ConditionItem
{
    protected $property;
    protected $operator;
    protected $value;

    protected $keywordMapping = [
        ConditionParser::IN_OPERATOR_TOKEN => 'terms',
        ConditionParser::NOT_IN_OPERATOR_TOKEN => 'terms',
        ConditionParser::EQUAL_OPERATOR_TOKEN => 'term',
        ConditionParser::NON_EQUAL_OPERATOR_TOKEN => 'term',
        ConditionParser::GT_OPERATOR => 'range',
        ConditionParser::GTE_OPERATOR => 'range',
        ConditionParser::LT_OPERATOR => 'range',
        ConditionParser::LTE_OPERATOR => 'range',
    ];

    protected $rangeOperatorMapping = [
        ConditionParser::GT_OPERATOR => 'gt',
        ConditionParser::GTE_OPERATOR => 'gte',
        ConditionParser::LT_OPERATOR => 'lt',
        ConditionParser::LTE_OPERATOR => 'lte',
    ];

    /**
     * @return mixed
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @param mixed $property
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * @return mixed
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param mixed $operator
     */
    public function setOperator($operator)
    {
        if (null === $this->getProperty()) {
            throw new \LogicException("Condition parsing error: property name expected, got `{$operator}`");
        }
        $this->operator = $operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        if (null === $this->getProperty()) {
            throw new \LogicException("Condition parsing error: property name should not be surrounded with quotes, got `{$value}`");
        }
        if (null === $this->getOperator()) {
            throw new \LogicException("Condition parsing error: comparison operator expected, got `{$value}`");
        }
        if (ConditionParser::IN_OPERATOR_TOKEN === $this->getOperator() && !is_array($value)) {
            throw new \LogicException("Condition parsing error: array is expected after IN operator, got `{$value}`");
        }
        if (ConditionParser::IN_OPERATOR_TOKEN !== $this->getOperator() && is_array($value)) {
            throw new \LogicException("Condition parsing error: array should be only after IN operator, got `{$value}`");
        }
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKeyword()
    {
        return $this->keywordMapping[$this->operator];
    }

    /**
     * @return string
     */
    public function getRangeOperator()
    {
        return $this->rangeOperatorMapping[$this->operator];
    }
}
