<?php

namespace CodeSteppers\Generated\Route\Codestepper;

use CodeSteppers\Generated\Codestepper\Save\SaveController;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Repository\Codestepper\SqlLister;
use CodeSteppers\Generated\Repository\Codestepper\SqlSaver;
use CodeSteppers\Generated\Route\RouterFn;
use CodeSteppers\Generated\Slugifier\Slugifier;
use CodeSteppers\Generated\ValidationError;
use CodeSteppers\Generated\Repository\Auth\JwtTokenVerifier;
use CodeSteppers\Generated\Route\Auth\AuthHeaderParser;
use CodeSteppers\Generated\Request;
use mysqli;

class CodestepperSaver implements RouterFn
{
    public function getRoute(Request $request): string
    {
       

        header("Content-Type: application/json");
        return json_encode((new SaveController(
            new SqlSaver($request->connection),
            new SqlLister($request->connection),
            new ValidationError(),
            new OperationError(),
            new Slugifier())
        )
            ->save($request->body ?? []), JSON_UNESCAPED_UNICODE);
    }
}
  