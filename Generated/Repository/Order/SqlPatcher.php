<?php

namespace CodeSteppers\Generated\Repository\Order;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Order\Patch\PatchedOrder;
use CodeSteppers\Generated\Order\Patch\Patcher;
use CodeSteppers\Generated\Order\Order;
use mysqli;

class SqlPatcher implements Patcher
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function patch(string $id, PatchedOrder $entity): Order
    {
        try {
          $byId = (new SqlByIdGetter($this->connection))->byId($id);
          $merged = $this->merge($byId, $entity);
          
          $stmt = $this->connection->prepare(
              'UPDATE `orders` SET 
                `status` = ?, `count` = ?, `totalCount` = ?
                WHERE `id` = ?;'
          );
          
          call_user_func(function (...$params) use ($stmt) {
                $stmt->bind_param(
                    "siis",
                    ...$params
                );
            },
                $merged->getStatus(),
        $merged->getCount(),
        $merged->getTotalCount(), $id);
          
          
          $stmt->execute();
          
          if ($stmt->error) {
              throw new OperationError($stmt->error);
          }
          
          return new Order($id, $byId->getSubscriberId(),$byId->getPlan(),$byId->getRef(),$merged->getStatus(),$merged->getCount(),$merged->getTotalCount(),$byId->getCreatedAt());
      
      } catch (\Error $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
          throw new OperationError("patch error");
      } catch (\Exception $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
          throw new OperationError("patch error");
      }
    }

    private function merge(Order $prev, PatchedOrder $patched): PatchedOrder
    {
        return new PatchedOrder(
            $patched->getStatus() !== null ? $patched->getStatus() : $prev->getStatus(), $patched->getCount() !== null ? $patched->getCount() : $prev->getCount(), $patched->getTotalCount() !== null ? $patched->getTotalCount() : $prev->getTotalCount()
        );
    }
}

