<?php

namespace CodeSteppers\Generated\Auth;

class RegistrationController
{
    private $saver;
    private $userByEmail;
    private $tokenGetter;
    private $refreshTokenSaver;

    public function __construct(UserSaver $saver, UserByEmailGetter $getter, TokenGetter $tokenGetter, RefreshTokenSaver $refreshTokenSaver)
    {
        $this->saver = $saver;
        $this->tokenGetter = $tokenGetter;
        $this->userByEmail = $getter;
        $this->refreshTokenSaver = $refreshTokenSaver;
    }

    public function register(array $user): Token
    {
        $storedUser = $this->userByEmail->getUser($user['email']);

        if ($storedUser->getEmail() !== '') {
            throw new AuthException('user exists');
        }

        $user = $this->saver->save(new NewUser($user['email'], password_hash($user['password'], PASSWORD_DEFAULT)));

        $tokens = new Token($this->tokenGetter->getAccessToken($user->getId()), $this->tokenGetter->getRefreshToken());
        $this->refreshTokenSaver->save(new RawToken($user->getId(), $tokens->getRefreshToken()));
        return $tokens;
    }
}
