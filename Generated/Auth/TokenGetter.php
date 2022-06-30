<?php

namespace CodeSteppers\Generated\Auth;

interface TokenGetter
{
    function getAccessToken(string $userId): AccessToken;

    function getRefreshToken(): RefreshToken;
}