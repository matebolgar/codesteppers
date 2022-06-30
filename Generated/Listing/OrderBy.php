<?php

namespace CodeSteppers\Generated\Listing;

class OrderBy
{
    const VALUES = ['asc', 'desc'];

    private $key;
    private $value;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;

        if (!in_array($value, self::VALUES)) {
            $this->value = '';
            return;
        }
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }
    public function getValue(): string
    {
        return $this->value;
    }
}

  