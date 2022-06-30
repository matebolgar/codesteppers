<?php

namespace Kodbazis;

class ClosureWrapper
{
    private $closure;

    public function __construct($closure)
    {
        $this->closure = $closure;
    }

    public function execute()
    {
        return ($this->closure)(...func_get_args());
    }
}