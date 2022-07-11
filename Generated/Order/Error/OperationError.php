<?php

namespace CodeSteppers\Generated\Order\Error;

use Throwable;

interface OperationError extends Throwable
{
    public function addField(array $field);
}
  