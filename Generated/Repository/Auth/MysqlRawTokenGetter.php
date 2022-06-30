<?php

namespace CodeSteppers\Generated\Repository\Auth;

use CodeSteppers\Generated\Auth\RawToken;
use CodeSteppers\Generated\Auth\RawTokenGetter;
use CodeSteppers\Generated\Auth\RefreshToken;

class MysqlRawTokenGetter implements RawTokenGetter
{
    private $connection;

    public function __construct(\mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function getRawToken(string $refreshTokenValue): ?RawToken
    {
        $stmt = $this->connection->prepare('SELECT * FROM `tokens` WHERE value = ?');
        $stmt->bind_param('s', $refreshTokenValue);

        $stmt->execute();
        $result = $stmt->get_result();

        $results = [];
        while ($data = $result->fetch_assoc()) {
            $results[] = $data;
        }

        if (count($results) === 0) {
            return null;
        }

        return new RawToken($results[0]['userId'], new RefreshToken($results[0]['value']));
    }


}