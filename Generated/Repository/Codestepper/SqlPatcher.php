<?php

namespace CodeSteppers\Generated\Repository\Codestepper;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Codestepper\Patch\PatchedCodestepper;
use CodeSteppers\Generated\Codestepper\Patch\Patcher;
use CodeSteppers\Generated\Codestepper\Codestepper;
use mysqli;

class SqlPatcher implements Patcher
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function patch(string $id, PatchedCodestepper $entity): Codestepper
    {
        try {
          $byId = (new SqlByIdGetter($this->connection))->byId($id);
          $merged = $this->merge($byId, $entity);
          
          $stmt = $this->connection->prepare(
              'UPDATE `codesteppers` SET 
                `subscriberId` = ?, `guestId` = ?, `title` = ?
                WHERE `id` = ?;'
          );
          
          call_user_func(function (...$params) use ($stmt) {
                $stmt->bind_param(
                    "isss",
                    ...$params
                );
            },
                $merged->getSubscriberId(),
        $merged->getGuestId(),
        $merged->getTitle(), $id);
          
          
          $stmt->execute();
          
          if ($stmt->error) {
              throw new OperationError($stmt->error);
          }
          
          return new Codestepper($id, $byId->getSlug(),$merged->getSubscriberId(),$merged->getGuestId(),$merged->getTitle(),$byId->getCreatedAt());
      
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

    private function merge(Codestepper $prev, PatchedCodestepper $patched): PatchedCodestepper
    {
        return new PatchedCodestepper(
            $patched->getSubscriberId() !== null ? $patched->getSubscriberId() : $prev->getSubscriberId(), $patched->getGuestId() !== null ? $patched->getGuestId() : $prev->getGuestId(), $patched->getTitle() !== null ? $patched->getTitle() : $prev->getTitle()
        );
    }
}

