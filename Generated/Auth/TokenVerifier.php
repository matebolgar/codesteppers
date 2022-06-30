<?php

namespace CodeSteppers\Generated\Auth;

interface TokenVerifier
{
    public function verify(string $token): ?Claims;
}
