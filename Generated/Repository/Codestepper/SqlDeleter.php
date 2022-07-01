<?php

namespace CodeSteppers\Generated\Repository\Codestepper;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Codestepper\Delete\Deleter;
use mysqli;
use CodeSteppers\Generated\Codestepper\Codestepper;

class SqlDeleter implements Deleter
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function delete(string $id): string
    {
        try { 
          $statement = $this->connection->prepare('DELETE FROM `codesteppers` WHERE `id` = ?');
          $statement->bind_param('s', $id);
          $statement->execute();
  
          return $id;   
        } catch (\Error $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
              var_dump($exception);
              exit;
            }
            throw new OperationError("delete error");
        } catch (\Exception $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
              var_dump($exception);
              exit;
            }
            throw new OperationError("delete error");
        }
    }
}
