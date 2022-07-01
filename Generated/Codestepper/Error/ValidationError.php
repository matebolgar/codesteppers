<?php

namespace CodeSteppers\Generated\Codestepper\Error;

use Throwable;

interface ValidationError extends Throwable
{
    public function addErrors(array $fields);
}
  