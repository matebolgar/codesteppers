<?php

namespace CodeSteppers\Generated\Auth;

use Exception;
use JsonSerializable;

class ExpiredException extends Exception implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            'error' => 'tokenExpired'
        ];
    }
}
