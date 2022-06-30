<?php

namespace CodeSteppers\Generated\Repository\Subscriber;

use CodeSteppers\Generated\OperationError;
use CodeSteppers\Generated\Subscriber\Save\NewSubscriber;
use CodeSteppers\Generated\Subscriber\Save\Saver;
use CodeSteppers\Generated\Subscriber\Subscriber;
use mysqli;

class SqlSaver implements Saver
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function Save(NewSubscriber $new): Subscriber
    {
        try {
            $statement = $this->connection->prepare(
                "INSERT INTO `subscribers` (
                `id`, `email`, `password`, `isVerified`, `verificationToken`, `createdAt`, `isUnsubscribed`
                ) 
                VALUES (NULL, ?,?,?,?,?,?);"
            );
    
            $email = $new->getEmail();
        $password = $new->getPassword();
        $isVerified = $new->getIsVerified();
        $verificationToken = $new->getVerificationToken();
        $createdAt = $new->getCreatedAt();
        $isUnsubscribed = $new->getIsUnsubscribed();
        
    
            $statement->bind_param(
                "ssisii",
                 $email, $password, $isVerified, $verificationToken, $createdAt, $isUnsubscribed        
             );
            $statement->execute();
    
            return new Subscriber((string)$statement->insert_id, $new->getEmail(),$new->getPassword(),$new->getIsVerified(),$new->getVerificationToken(),$new->getCreatedAt(),$new->getIsUnsubscribed());
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

