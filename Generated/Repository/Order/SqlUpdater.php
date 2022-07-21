<?php

namespace CodeSteppers\Generated\Repository\Order;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Order\Update\UpdatedOrder;
use CodeSteppers\Generated\Order\Update\Updater;
use CodeSteppers\Generated\Order\Order;
use mysqli;

class SqlUpdater implements Updater
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function update(string $id, UpdatedOrder $entity): Order
    {
        try {
          $byId = (new SqlByIdGetter($this->connection))->byId($id);
          
          $stmt = $this->connection->prepare(
              'UPDATE `orders` SET 
                `status` = ?, `count` = ?
                WHERE `id` = ?;'
          );
          
          $status= $entity->getStatus();
        $count= $entity->getCount();
         
          $stmt->bind_param(
              "sis",
               $status, $count, $id        
          );
          $stmt->execute();
          
          return new Order($id, $byId->getSubscriberId(),$byId->getPlan(),$byId->getRef(),$entity->getStatus(),$entity->getCount(),$byId->getCreatedAt());
      
      } catch (\Error $exception) {
          if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
            var_dump($exception);
            exit;
          }
          throw new OperationError("update error");
      } catch (\Exception $exception) {
          if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
            var_dump($exception);
            exit;
          }
          throw new OperationError("update error");
      }
    }
}

