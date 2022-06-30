<?php

namespace CodeSteppers\Generated\Subscriber\Error;

use Throwable;

interface ValidationError extends Throwable
{
    public function addErrors(array $fields);
}
  