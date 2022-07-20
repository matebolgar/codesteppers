<?php

namespace CodeSteppers\Generated\Repository\Message;

use mysqli;
use CodeSteppers\Generated\Message\ById\ById;
use CodeSteppers\Generated\Message\Message;
use CodeSteppers\Generated\OperationError;

class SqlByIdGetter implements ById
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function byId(string $id): Message
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM `messages` WHERE id = ?');
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return new Message((int)$result['id'], (string)$result['email'], (string)$result['subject'], (string)$result['body'], (string)$result['status'], (int)$result['numberOfAttempts'], (int)$result['sentAt'], (int)$result['createdAt']);
        
        } catch (\Error $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
            throw new OperationError("by id error");
        } catch (\Exception $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
            throw new OperationError("by id error");
        }
    }
}

