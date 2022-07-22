<?php

namespace CodeSteppers\Generated\Repository\Subscriber;

use mysqli;
use CodeSteppers\Generated\Subscriber\ById\ById;
use CodeSteppers\Generated\Subscriber\Subscriber;
use CodeSteppers\Generated\OperationError;

class SqlByIdGetter implements ById
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function byId(string $id): Subscriber
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM `subscribers` WHERE id = ?');
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return new Subscriber($result['id'] === null ? null : (int)$result['id'],
                        (string)$result['email'],
                        $result['password'] === null ? null : (string)$result['password'],
                        $result['isVerified'] === null ? null : (bool)$result['isVerified'],
                        $result['verificationToken'] === null ? null : (string)$result['verificationToken'],
                        $result['createdAt'] === null ? null : (int)$result['createdAt'],
                        $result['isUnsubscribed'] === null ? null : (bool)$result['isUnsubscribed']);
        
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

