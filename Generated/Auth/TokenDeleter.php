<?php

namespace CodeSteppers\Generated\Auth;

interface TokenDeleter
{
    public function delete(RefreshToken $token): ?RawToken;
}