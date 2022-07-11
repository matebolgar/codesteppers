<?php

namespace CodeSteppers\Generated\Route\Order;

use CodeSteppers\Generated\Order\Listing\ListController;
use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Paging\Pager;
use CodeSteppers\Generated\Repository\Order\SqlLister;
use CodeSteppers\Generated\Route\Error;
use CodeSteppers\Generated\Route\RouterFn;
use CodeSteppers\Generated\Repository\Auth\JwtTokenVerifier;
use CodeSteppers\Generated\Route\Auth\AuthHeaderParser;
use CodeSteppers\Generated\Request;
use mysqli;

class OrderLister implements RouterFn
{
    public function getRoute(Request $request): string
    {
        

        $query = $request->query;
        Error::validateQueryParams($query, ['from', 'limit']);

        if (isset($query['filters'])) {
            $query['filters'] = (array)json_decode(($query['filters'] ?? ''), true);
        }

        if (isset($query['orderBy'])) {
            $query['orderBy'] = (array)json_decode(($query['orderBy'] ?? ''), true);
        }

        header("Content-Type: application/json");
        return json_encode((new ListController(
            new OperationError(),
            new SqlLister($request->connection),
            new Pager())
        )->list($query), JSON_UNESCAPED_UNICODE);
    }
}
  