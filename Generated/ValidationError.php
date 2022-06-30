<?php

namespace CodeSteppers\Generated;

use Exception;
use JsonSerializable;
use Throwable;

class ValidationError extends Exception implements JsonSerializable, 
\CodeSteppers\Generated\Subscriber\Error\ValidationError
{
    private $fields = [];

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct();
        $this->message = [];
    }

    public function addErrors(array $fields)
    {
        $this->fields = $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function jsonSerialize()
    {
        return [
            "error" => [
                'errors' => $this->fields,
                'code' => 400,
                'message' => "Validation error",
            ],
        ];
    }
}
