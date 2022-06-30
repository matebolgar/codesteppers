<?php

namespace CodeSteppers\Generated\Auth;

use JsonSerializable;

class AccessToken implements JsonSerializable
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return [
            'accessToken' => $this->value,
        ];
    }


}
