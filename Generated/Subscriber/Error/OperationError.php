<?php

namespace CodeSteppers\Generated\Subscriber\Error;

use Throwable;

interface OperationError extends Throwable
{
    public function addField(array $field);
}
  