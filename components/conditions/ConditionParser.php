<?php

namespace mikemadisonweb\elasticsearch\components\conditions;

class ConditionParser
{
    const SEPARATOR_TOKEN = 'SEPARATOR';
    const AND_CONDITION_TOKEN = 'AND_CONDITION';
    const OR_CONDITION_TOKEN = 'OR_CONDITION';
    const NOT_IN_OPERATOR_TOKEN = 'NOT_IN_OPERATOR';
    const IN_OPERATOR_TOKEN = 'IN_OPERATOR';
    const EQUAL_OPERATOR_TOKEN = 'EQUAL_OPERATOR';
    const NON_EQUAL_OPERATOR_TOKEN = 'NON_EQUAL_OPERATOR';
    const GT_OPERATOR = 'GT_OPERATOR';
    const GTE_OPERATOR = 'GTE_OPERATOR';
    const LT_OPERATOR = 'LT_OPERATOR';
    const LTE_OPERATOR = 'LTE_OPERATOR';
    const GROUP_START_TOKEN = 'GROUP_START';
    const GROUP_END_TOKEN = 'GROUP_END';
    const ARRAY_SEPARATOR_TOKEN = 'ARRAY_SEPARATOR';
    const ARRAY_START_TOKEN = 'ARRAY_START';
    const ARRAY_END_TOKEN = 'ARRAY_END';
    const FIELD_STRING_VALUE_TOKEN = 'FIELD_STRING_VALUE';
    const FIELD_NUMERIC_VALUE_TOKEN = 'FIELD_NUMERIC_VALUE';
    const FIELD_NAME_TOKEN = 'FIELD_NAME';

    protected $regex;

    protected $conditionMap = [
        'and' => self::AND_CONDITION_TOKEN,
        'or' => self::OR_CONDITION_TOKEN,
    ];

    protected $operatorMap = [
        'in' => self::IN_OPERATOR_TOKEN,
        'not in' => self::NOT_IN_OPERATOR_TOKEN,
        '=' => self::EQUAL_OPERATOR_TOKEN,
        '!=' => self::NON_EQUAL_OPERATOR_TOKEN,
        '>=' => self::GTE_OPERATOR,
        '>' => self::GT_OPERATOR,
        '<=' => self::LTE_OPERATOR,
        '<' => self::LT_OPERATOR,
    ];

    protected $groupingMap = [
        '\s' => self::SEPARATOR_TOKEN,
        '\(' => self::GROUP_START_TOKEN,
        '\)' => self::GROUP_END_TOKEN,
        '\[' => self::ARRAY_START_TOKEN,
        '\]' => self::ARRAY_END_TOKEN,
        ',' => self::ARRAY_SEPARATOR_TOKEN,
        '(["\'])(.*?[^\\\])\19' => self::FIELD_STRING_VALUE_TOKEN,
        '[0-9]+' => self::FIELD_NUMERIC_VALUE_TOKEN,
        '[^\s,()\[\]]+' => self::FIELD_NAME_TOKEN,
    ];

    protected $tokens = [
        2 => self::AND_CONDITION_TOKEN,
        3 => self::OR_CONDITION_TOKEN,
        4 => self::IN_OPERATOR_TOKEN,
        5 => self::NOT_IN_OPERATOR_TOKEN,
        6 => self::EQUAL_OPERATOR_TOKEN,
        7 => self::NON_EQUAL_OPERATOR_TOKEN,
        8 => self::GTE_OPERATOR,
        9 => self::GT_OPERATOR,
        10 => self::LTE_OPERATOR,
        11 => self::LT_OPERATOR,
        12 => self::SEPARATOR_TOKEN,
        13 => self::GROUP_START_TOKEN,
        14 => self::GROUP_END_TOKEN,
        15 => self::ARRAY_START_TOKEN,
        16 => self::ARRAY_END_TOKEN,
        17 => self::ARRAY_SEPARATOR_TOKEN,
        20 => self::FIELD_STRING_VALUE_TOKEN,
        21 => self::FIELD_NUMERIC_VALUE_TOKEN,
        22 => self::FIELD_NAME_TOKEN,
    ];

    public function __construct()
    {
        $tokenMap = array_merge($this->conditionMap, $this->operatorMap, $this->groupingMap);
        $this->regex = '/((' . implode(')|(', array_keys($tokenMap)) . '))/s';
    }

    public function parse($exprString)
    {
        $lexed = $this->lex($exprString);

        return $this->parseConditionItems($lexed);
    }

    /**
     * @param $exprString
     * @return array
     * @throws \Exception
     */
    public function lex($exprString)
    {
        $lexed = [];
        $found = preg_match_all($this->regex, $exprString, $matches);
        foreach ($matches[0] as $matchOffset => $match) {
            foreach ($this->tokens as $tokenOffset => $token) {
                $withoutQuotes = $this->trimQuotes($match);
                $byToken = $matches[$tokenOffset][$matchOffset];
                if ($byToken === $withoutQuotes) {
                    $lexed[] = [
                        $withoutQuotes,
                        $token,
                    ];
                }
            }
        }

        if (count($lexed) !== $found) {
            throw new \Exception("Condition lexing failed: {$exprString}");
        }

        return $lexed;
    }

    /**
     * Recursively parse conditions to determine logical expressions and groups
     * @param array $lexed
     * @param int $offset
     * @param bool $subGroup
     * @return array|Group
     * @throws \Exception
     */
    protected function parseConditionItems(array $lexed, $offset = 0, $subGroup = false)
    {
        $arrayStarted = false;
        $arrayElement = [];
        $currentExpression = new Expression();
        $currentGroup = new Group();
        for ($i = $offset; $i < count($lexed); $i++) {
            list($element, $type) = $lexed[$i];
            switch ($type) {
                case self::SEPARATOR_TOKEN:
                case self::ARRAY_SEPARATOR_TOKEN:
                    break;
                case self::OR_CONDITION_TOKEN:
                case self::AND_CONDITION_TOKEN:
                    $currentGroup->setCondition($type);
                    $currentExpression = new Expression();
                    break;
                case self::GROUP_START_TOKEN:
                    $recursive = $this->parseConditionItems($lexed, $i + 1, true);
                    if (!is_array($recursive)) {
                        throw new \LogicException("Condition parsing failed: unclosed `{$element}` parenthesis found");
                    }
                    list($subCondition, $position) = $recursive;
                    $i = $position;
                    $currentGroup->addItem($subCondition);
                    break;
                case self::GROUP_END_TOKEN:
                    if (!$subGroup) {
                        throw new \LogicException("Condition parsing failed: one of `{$element}` does not have an open bracket or unnecessary");
                    }

                    return [$currentGroup, $i];
                case self::FIELD_NAME_TOKEN:
                    $currentExpression = new Expression();
                    $currentGroup->addItem($currentExpression);
                    $currentExpression->setProperty($element);
                    break;
                case self::FIELD_STRING_VALUE_TOKEN:
                case self::FIELD_NUMERIC_VALUE_TOKEN:
                    if ($arrayStarted) {
                        $arrayElement[] = $element;
                    } else {
                        $currentExpression->setValue($element);
                    }
                    break;
                case self::IN_OPERATOR_TOKEN:
                case self::NOT_IN_OPERATOR_TOKEN:
                case self::EQUAL_OPERATOR_TOKEN:
                case self::NON_EQUAL_OPERATOR_TOKEN:
                case self::GT_OPERATOR:
                case self::GTE_OPERATOR:
                case self::LT_OPERATOR:
                case self::LTE_OPERATOR:
                    $currentExpression->setOperator($type);
                    break;
                case self::ARRAY_START_TOKEN:
                    $arrayStarted = true;
                    break;
                case self::ARRAY_END_TOKEN:
                    if (!$arrayStarted) {
                        throw new \LogicException("Condition parsing failed: one of `{$element}` does not have an open bracket or unnecessary");
                    }
                    $arrayStarted = false;
                    $currentExpression->setValue($arrayElement);
                    $arrayElement = [];
                    break;
                default:
                    throw new \Exception("Unknown type: {$type}");
            }
        }
        if ($subGroup) {
            return [$currentGroup, $i];
        } else {
            return $currentGroup;
        }
    }

    /**
     * @param $input
     * @return mixed
     */
    protected function trimQuotes($input)
    {
        return preg_replace('/(^[\"\']|[\"\']$)/', '', $input);
    }
}
