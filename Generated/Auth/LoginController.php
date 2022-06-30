<?php

namespace CodeSteppers\Generated\Auth;

class LoginController
{
    private $tokenGetter;

    private $credentialsGetter;

    private $refreshTokenSaver;

    public function __construct(TokenGetter $tokenGetter, UserByEmailGetter $credentialsGetter, RefreshTokenSaver $refreshTokenSaver)
    {
        $this->tokenGetter = $tokenGetter;
        $this->credentialsGetter = $credentialsGetter;
        $this->refreshTokenSaver = $refreshTokenSaver;
    }

    public function authenticate(array $credentials): Token
    {
        $user = $this->credentialsGetter->getUser($credentials['email'] ?? '');

        if (!password_verify($credentials['password'], $user->getPassword())) {
            throw new AuthException('unauthorized');
        }

        $access = $this->tokenGetter->getAccessToken($user->getId());
        $refresh = $this->tokenGetter->getRefreshToken();
        $this->refreshTokenSaver->save(new RawToken($user->getId(), $refresh));
        return new Token($access, $refresh);
    }
}