<?php

namespace CodeSteppers\Generated\Listing;

class Clause
{
    const OPERATORS = ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'nin', 'like'];
    private $operator;
    private $key;
    private $value;

    public function __construct($operator, $key, $value)
    {
        $this->key = $key;
        $this->value = $value;
        $this->operator = in_array($operator, self::OPERATORS) ? $operator : '';
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getKey(): string
    {
        return $this->key;
    }
}

  