<?php

namespace CodeSteppers\Generated\Message\Error;

use Throwable;

interface ValidationError extends Throwable
{
    public function addErrors(array $fields);
}
  