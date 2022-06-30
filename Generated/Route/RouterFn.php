<?php

namespace CodeSteppers\Generated\Route;

use CodeSteppers\Generated\Request;
use mysqli;

interface RouterFn {
    public function getRoute(Request $request): string;
}

  