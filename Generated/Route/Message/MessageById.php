<?php

namespace CodeSteppers\Generated\Route\Message;

use CodeSteppers\Generated\Message\ById\ByIdController;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Repository\Message\SqlByIdGetter;
use CodeSteppers\Generated\Route\RouterFn;
use CodeSteppers\Generated\Repository\Auth\JwtTokenVerifier;
use CodeSteppers\Generated\Route\Auth\AuthHeaderParser;
use CodeSteppers\Generated\Request;
use mysqli;

class MessageById implements RouterFn
{
    public function getRoute(Request $request): string
    {
        

        header("Content-Type: application/json");
        return json_encode((new ByIdController(
            new SqlByIdGetter($request->connection),
            new OperationError())
        )
            ->byId($request->vars['id']), JSON_UNESCAPED_UNICODE);
    }
}

  