<?php

namespace CodeSteppers\Generated\Auth;

use JsonSerializable;

class Token implements JsonSerializable
{
    private $accessToken;
    private $refreshToken;

    public function __construct(AccessToken $accessToken, RefreshToken $refreshToken)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): RefreshToken
    {
        return $this->refreshToken;
    }


    public function jsonSerialize()
    {
        return [
            'accessToken' => $this->accessToken->getValue(),
            'refreshToken' => $this->refreshToken->getValue(),
        ];
    }
}
