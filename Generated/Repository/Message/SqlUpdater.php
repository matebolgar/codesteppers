<?php

namespace CodeSteppers\Generated\Repository\Message;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Message\Update\UpdatedMessage;
use CodeSteppers\Generated\Message\Update\Updater;
use CodeSteppers\Generated\Message\Message;
use mysqli;

class SqlUpdater implements Updater
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function update(string $id, UpdatedMessage $entity): Message
    {
        try {
          $byId = (new SqlByIdGetter($this->connection))->byId($id);
          
          $stmt = $this->connection->prepare(
              'UPDATE `messages` SET 
                `status` = ?, `numberOfAttempts` = ?, `sentAt` = ?
                WHERE `id` = ?;'
          );
          
          $status= $entity->getStatus();
        $numberOfAttempts= $entity->getNumberOfAttempts();
        $sentAt= $entity->getSentAt();
         
          $stmt->bind_param(
              "siis",
               $status, $numberOfAttempts, $sentAt, $id        
          );
          $stmt->execute();
          
          return new Message($id, $byId->getEmail(),$byId->getSubject(),$byId->getBody(),$entity->getStatus(),$entity->getNumberOfAttempts(),$entity->getSentAt(),$byId->getCreatedAt());
      
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

