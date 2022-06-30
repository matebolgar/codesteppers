<?php

namespace CodeSteppers\Generated\Repository\Auth;

use CodeSteppers\Generated\Auth\User;
use CodeSteppers\Generated\Auth\UserByEmailGetter;

class MySqlUserByEmailGetter implements UserByEmailGetter
{
    private $connection;

    public function __construct(\mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function getUser(string $email): User
    {
        $stmt = $this->connection->prepare('SELECT * FROM `users` WHERE email = ?');
        $stmt->bind_param('s', $email);

        $stmt->execute();
        $result = $stmt->get_result();

        $results = [];
        while ($data = $result->fetch_assoc()) {
            $results[] = $data;
        }

        if (count($results) === 0) {
            return new User('', '', '');
        }

        return new User($results[0]['id'], $results[0]['email'], $results[0]['password']);
    }

}