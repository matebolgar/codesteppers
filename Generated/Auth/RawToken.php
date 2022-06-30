<?php

namespace CodeSteppers\Generated\Auth;

class RawToken
{
    private $userId;
    private $refreshToken;

    public function __construct(string $userId, RefreshToken $refreshToken)
    {
        $this->userId = $userId;
        $this->refreshToken = $refreshToken;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refreshToken;
    }
}
