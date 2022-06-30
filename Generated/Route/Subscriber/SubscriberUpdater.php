<?php

namespace CodeSteppers\Generated\Route\Subscriber;

use CodeSteppers\Generated\Subscriber\Update\UpdateController;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Repository\Subscriber\SqlUpdater;
use CodeSteppers\Generated\ValidationError;
use CodeSteppers\Generated\Route\RouterFn;
use CodeSteppers\Generated\Repository\Auth\JwtTokenVerifier;
use CodeSteppers\Generated\Route\Auth\AuthHeaderParser;
use CodeSteppers\Generated\Request;
use mysqli;

class SubscriberUpdater implements RouterFn
{
    public function getRoute(Request $request): string
    {
        
    
        header("Content-Type: application/json");
        return json_encode((new UpdateController(
            new SqlUpdater($request->connection),
            new OperationError(),
            new ValidationError()
        ))
            ->update($request->body ?? [], $request->vars['id']), JSON_UNESCAPED_UNICODE);
    }
}

  