<?php

namespace CodeSteppers\Generated\Repository\Order;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Order\Save\NewOrder;
use CodeSteppers\Generated\Order\Save\Saver;
use CodeSteppers\Generated\Order\Order;
use mysqli;

class SqlSaver implements Saver
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function Save(NewOrder $new): Order
    {
        try {
            $statement = $this->connection->prepare(
                "INSERT INTO `orders` (
                `id`, `subscriberId`, `plan`, `ref`, `status`, `count`, `totalCount`, `createdAt`
                ) 
                VALUES (NULL, ?,?,?,?,?,?,?);"
            );
    
            $subscriberId = $new->getSubscriberId();
        $plan = $new->getPlan();
        $ref = $new->getRef();
        $status = $new->getStatus();
        $count = $new->getCount();
        $totalCount = $new->getTotalCount();
        $createdAt = $new->getCreatedAt();
        
    
            $statement->bind_param(
                "isssiii",
                 $subscriberId, $plan, $ref, $status, $count, $totalCount, $createdAt        
             );
            $statement->execute();
    
            return new Order((string)$statement->insert_id, $new->getSubscriberId(),$new->getPlan(),$new->getRef(),$new->getStatus(),$new->getCount(),$new->getTotalCount(),$new->getCreatedAt());
        } catch (\Error $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
            throw new OperationError("save error");
        } catch (\Exception $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
            throw new OperationError("save error");
        }
    }
}

