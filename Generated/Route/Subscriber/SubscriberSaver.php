<?php

namespace CodeSteppers\Generated\Route\Subscriber;

use CodeSteppers\Generated\Subscriber\Save\SaveController;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Repository\Subscriber\SqlLister;
use CodeSteppers\Generated\Repository\Subscriber\SqlSaver;
use CodeSteppers\Generated\Route\RouterFn;
use CodeSteppers\Generated\Slugifier\Slugifier;
use CodeSteppers\Generated\ValidationError;
use CodeSteppers\Generated\Repository\Auth\JwtTokenVerifier;
use CodeSteppers\Generated\Route\Auth\AuthHeaderParser;
use CodeSteppers\Generated\Request;
use mysqli;

class SubscriberSaver implements RouterFn
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
  