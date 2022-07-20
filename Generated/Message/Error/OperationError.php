<?php

namespace CodeSteppers\Generated\Message\Error;

use Throwable;

interface OperationError extends Throwable
{
    public function addField(array $field);
}
  