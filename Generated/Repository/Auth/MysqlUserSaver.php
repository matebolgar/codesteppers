<?php

namespace CodeSteppers\Generated\Repository\Auth;


use mysqli;
use CodeSteppers\Generated\Auth\NewUser;
use CodeSteppers\Generated\Auth\User;
use CodeSteppers\Generated\Auth\UserSaver;

class MysqlUserSaver implements UserSaver
{
    private $connection;

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function save(NewUser $user): User
    {
        $stmt = $this->connection->prepare('INSERT INTO `users` (`id`, `email`, `password`) VALUES (NULL, ?, ?)');

        call_user_func(function ($email, $password) use ($stmt) {
            $stmt->bind_param('ss', $email, $password);
        }, $user->getEmail(), $user->getPassword());

        $stmt->execute();

        return new User((string)$stmt->insert_id, $user->getEmail(), $user->getPassword());
    }
}
