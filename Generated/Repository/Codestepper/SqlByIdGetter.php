<?php

namespace CodeSteppers\Generated\Repository\Codestepper;

use mysqli;
use CodeSteppers\Generated\Codestepper\ById\ById;
use CodeSteppers\Generated\Codestepper\Codestepper;
use CodeSteppers\Generated\OperationError;

class SqlByIdGetter implements ById
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function byId(string $id): Codestepper
    {
        try {
            $stmt = $this->connection->prepare('SELECT * FROM `codesteppers` WHERE id = ?');
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return new Codestepper($result['id'] === null ? null : (int)$result['id'],
                        (string)$result['slug'],
                        $result['subscriberId'] === null ? null : (int)$result['subscriberId'],
                        $result['guestId'] === null ? null : (string)$result['guestId'],
                        $result['title'] === null ? null : (string)$result['title'],
                        $result['createdAt'] === null ? null : (int)$result['createdAt']);
        
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

