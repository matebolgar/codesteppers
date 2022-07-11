<?php

namespace CodeSteppers\Generated\Order\Error;

use Throwable;

interface ValidationError extends Throwable
{
    public function addErrors(array $fields);
}
  