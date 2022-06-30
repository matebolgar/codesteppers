<?php

namespace CodeSteppers\Generated\Auth;

interface UserByEmailGetter
{
    public function getUser(string $email): User;
}