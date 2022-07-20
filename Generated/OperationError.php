<?php

namespace CodeSteppers\Generated;

use Exception;
use JsonSerializable;
use Throwable;

class OperationError extends Exception implements JsonSerializable, 
\CodeSteppers\Generated\Subscriber\Error\OperationError, \CodeSteppers\Generated\Codestepper\Error\OperationError, \CodeSteppers\Generated\Order\Error\OperationError, \CodeSteppers\Generated\Message\Error\OperationError
{
    private $fields = [];

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct();
        $this->message = [];
    }

    public function addField(array $field)
    {
        $this->fields[] = $field;
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
                'message' => "Operation error",
            ],
        ];
    }
}
