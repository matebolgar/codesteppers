<?php

namespace CodeSteppers\Generated\Auth;

use Exception;
use JsonSerializable;

class AuthException extends Exception implements JsonSerializable
{
    public function jsonSerialize()
    {
        return [
            'error' => 'unauthorized'
        ];
    }
}