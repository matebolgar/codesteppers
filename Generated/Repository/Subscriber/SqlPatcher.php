<?php

namespace CodeSteppers\Generated\Repository\Subscriber;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Subscriber\Patch\PatchedSubscriber;
use CodeSteppers\Generated\Subscriber\Patch\Patcher;
use CodeSteppers\Generated\Subscriber\Subscriber;
use mysqli;

class SqlPatcher implements Patcher
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function patch(string $id, PatchedSubscriber $entity): Subscriber
    {
        try {
          $byId = (new SqlByIdGetter($this->connection))->byId($id);
          $merged = $this->merge($byId, $entity);
          
          $stmt = $this->connection->prepare(
              'UPDATE `subscribers` SET 
                `email` = ?, `password` = ?, `isVerified` = ?, `verificationToken` = ?, `isUnsubscribed` = ?
                WHERE `id` = ?;'
          );
          
          call_user_func(function (...$params) use ($stmt) {
                $stmt->bind_param(
                    "ssisis",
                    ...$params
                );
            },
                $merged->getEmail(),
        $merged->getPassword(),
        $merged->getIsVerified(),
        $merged->getVerificationToken(),
        $merged->getIsUnsubscribed(), $id);
          
          
          $stmt->execute();
          
          if ($stmt->error) {
              throw new OperationError($stmt->error);
          }
          
          return new Subscriber($id, $merged->getEmail(),$merged->getPassword(),$merged->getIsVerified(),$merged->getVerificationToken(),$byId->getCreatedAt(),$merged->getIsUnsubscribed());
      
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

    private function merge(Subscriber $prev, PatchedSubscriber $patched): PatchedSubscriber
    {
        return new PatchedSubscriber(
            $patched->getEmail() !== null ? $patched->getEmail() : $prev->getEmail(), $patched->getPassword() !== null ? $patched->getPassword() : $prev->getPassword(), $patched->getIsVerified() !== null ? $patched->getIsVerified() : $prev->getIsVerified(), $patched->getVerificationToken() !== null ? $patched->getVerificationToken() : $prev->getVerificationToken(), $patched->getIsUnsubscribed() !== null ? $patched->getIsUnsubscribed() : $prev->getIsUnsubscribed()
        );
    }
}

