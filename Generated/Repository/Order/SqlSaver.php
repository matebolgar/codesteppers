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
                `id`, `subscriberId`, `plan`, `ref`, `status`, `createdAt`
                ) 
                VALUES (NULL, ?,?,?,?,?);"
            );
    
            $subscriberId = $new->getSubscriberId();
        $plan = $new->getPlan();
        $ref = $new->getRef();
        $status = $new->getStatus();
        $createdAt = $new->getCreatedAt();
        
    
            $statement->bind_param(
                "isssi",
                 $subscriberId, $plan, $ref, $status, $createdAt        
             );
            $statement->execute();
    
            return new Order((string)$statement->insert_id, $new->getSubscriberId(),$new->getPlan(),$new->getRef(),$new->getStatus(),$new->getCreatedAt());
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

