<?php

namespace CodeSteppers\Generated\Repository\Message;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Message\Patch\PatchedMessage;
use CodeSteppers\Generated\Message\Patch\Patcher;
use CodeSteppers\Generated\Message\Message;
use mysqli;

class SqlPatcher implements Patcher
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function patch(string $id, PatchedMessage $entity): Message
    {
        try {
          $byId = (new SqlByIdGetter($this->connection))->byId($id);
          $merged = $this->merge($byId, $entity);
          
          $stmt = $this->connection->prepare(
              'UPDATE `messages` SET 
                `status` = ?, `numberOfAttempts` = ?, `sentAt` = ?
                WHERE `id` = ?;'
          );
          
          call_user_func(function (...$params) use ($stmt) {
                $stmt->bind_param(
                    "siis",
                    ...$params
                );
            },
                $merged->getStatus(),
        $merged->getNumberOfAttempts(),
        $merged->getSentAt(), $id);
          
          
          $stmt->execute();
          
          if ($stmt->error) {
              throw new OperationError($stmt->error);
          }
          
          return new Message($id, $byId->getEmail(),$byId->getSubject(),$byId->getBody(),$merged->getStatus(),$merged->getNumberOfAttempts(),$merged->getSentAt(),$byId->getCreatedAt());
      
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

    private function merge(Message $prev, PatchedMessage $patched): PatchedMessage
    {
        return new PatchedMessage(
            $patched->getStatus() !== null ? $patched->getStatus() : $prev->getStatus(), $patched->getNumberOfAttempts() !== null ? $patched->getNumberOfAttempts() : $prev->getNumberOfAttempts(), $patched->getSentAt() !== null ? $patched->getSentAt() : $prev->getSentAt()
        );
    }
}

