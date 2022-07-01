<?php

namespace CodeSteppers\Generated\Route\Codestepper;

use CodeSteppers\Generated\Codestepper\Patch\PatchController;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Repository\Codestepper\SqlPatcher;
use CodeSteppers\Generated\ValidationError;
use CodeSteppers\Generated\Route\RouterFn;
use CodeSteppers\Generated\Repository\Auth\JwtTokenVerifier;
use CodeSteppers\Generated\Route\Auth\AuthHeaderParser;
use CodeSteppers\Generated\Request;
use mysqli;

class CodestepperPatcher implements RouterFn
{
    public function getRoute(Request $request): string
    {
       

        header("Content-Type: application/json");
        return json_encode((new PatchController(
            new SqlPatcher($request->connection),
            new OperationError(),
            new ValidationError()
        ))
            ->patch($request->body ?? [], $request->vars['id']), JSON_UNESCAPED_UNICODE);
    }
}

  