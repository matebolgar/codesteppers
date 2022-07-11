<?php

namespace CodeSteppers\Generated\Repository\Order;

use mysqli;
use CodeSteppers\Generated\Order\ById\ById;
use CodeSteppers\Generated\Order\Order;
use CodeSteppers\Generated\OperationError;

class SqlByIdGetter implements ById
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function byId(string $id): Order
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM `orders` WHERE id = ?');
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return new Order((int)$result['id'], (int)$result['subscriberId'], (string)$result['plan'], (string)$result['ref'], (string)$result['status'], (int)$result['createdAt']);
        
        } catch (\Error $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
            throw new OperationError("by id error");
        } catch (\Exception $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
            throw new OperationError("by id error");
        }
    }
}

