<?php

namespace CodeSteppers\Generated\Codestepper\Error;

use Throwable;

interface OperationError extends Throwable
{
    public function addField(array $field);
}
  