<?php

namespace CodeSteppers\Generated\Auth;

interface RawTokenGetter
{
    public function getRawToken(string $refreshTokenValue): ?RawToken;
}