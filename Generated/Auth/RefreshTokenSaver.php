<?php

namespace CodeSteppers\Generated\Auth;

interface RefreshTokenSaver
{
    public function save(RawToken $token): RefreshToken;
}