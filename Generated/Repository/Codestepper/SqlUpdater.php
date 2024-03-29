<?php

namespace CodeSteppers\Generated\Repository\Codestepper;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Codestepper\Update\UpdatedCodestepper;
use CodeSteppers\Generated\Codestepper\Update\Updater;
use CodeSteppers\Generated\Codestepper\Codestepper;
use mysqli;

class SqlUpdater implements Updater
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function update(string $id, UpdatedCodestepper $entity): Codestepper
    {
        try {
          $byId = (new SqlByIdGetter($this->connection))->byId($id);
          
          $stmt = $this->connection->prepare(
              'UPDATE `codesteppers` SET 
                `subscriberId` = ?, `guestId` = ?, `title` = ?
                WHERE `id` = ?;'
          );
          
          $subscriberId= $entity->getSubscriberId();
        $guestId= $entity->getGuestId();
        $title= $entity->getTitle();
         
          $stmt->bind_param(
              "isss",
               $subscriberId, $guestId, $title, $id        
          );
          $stmt->execute();
          
          return new Codestepper($id, $byId->getSlug(),$entity->getSubscriberId(),$entity->getGuestId(),$entity->getTitle(),$byId->getCreatedAt());
      
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

