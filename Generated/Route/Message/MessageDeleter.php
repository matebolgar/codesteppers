<?php

namespace CodeSteppers\Generated\Route\Message;

use CodeSteppers\Generated\Message\Delete\DeleteController;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Repository\Message\SqlDeleter;
use CodeSteppers\Generated\Route\RouterFn;
use CodeSteppers\Generated\Repository\Auth\JwtTokenVerifier;
use CodeSteppers\Generated\Route\Auth\AuthHeaderParser;
use CodeSteppers\Generated\Request;
use mysqli;

class MessageDeleter implements RouterFn
{
    public function getRoute(Request $request): string
    {
         

        header("Content-Type: application/json");
        return json_encode(['id' => (new DeleteController(
            new OperationError(),
            new SqlDeleter($request->connection))
        )
            ->delete($request->vars['id'])], JSON_UNESCAPED_UNICODE);
    }
}
  