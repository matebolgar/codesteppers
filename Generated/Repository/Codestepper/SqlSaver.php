<?php

namespace CodeSteppers\Generated\Repository\Codestepper;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Codestepper\Save\NewCodestepper;
use CodeSteppers\Generated\Codestepper\Save\Saver;
use CodeSteppers\Generated\Codestepper\Codestepper;
use mysqli;

class SqlSaver implements Saver
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function Save(NewCodestepper $new): Codestepper
    {
        try {
            $statement = $this->connection->prepare(
                "INSERT INTO `codesteppers` (
                `id`, `slug`, `subscriberId`, `title`, `createdAt`
                ) 
                VALUES (NULL, ?,?,?,?);"
            );
    
            $slug = $new->getSlug();
        $subscriberId = $new->getSubscriberId();
        $title = $new->getTitle();
        $createdAt = $new->getCreatedAt();
        
    
            $statement->bind_param(
                "sisi",
                 $slug, $subscriberId, $title, $createdAt        
             );
            $statement->execute();
    
            return new Codestepper((string)$statement->insert_id, $new->getSlug(),$new->getSubscriberId(),$new->getTitle(),$new->getCreatedAt());
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

