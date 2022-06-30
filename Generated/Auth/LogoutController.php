<?php

namespace CodeSteppers\Generated\Auth;

class LogoutController
{
    private $tokenDeleter;

    public function __construct(TokenDeleter $tokenDeleter)
    {
        $this->tokenDeleter = $tokenDeleter;
    }

    public function logout(string $refreshTokenValue)
    {
        $deleted = $this->tokenDeleter->delete(new RefreshToken($refreshTokenValue));
        if (!$deleted) {
            throw new AuthException();
        }

        return ['success' => true];
    }
}
