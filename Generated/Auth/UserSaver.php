<?php

namespace CodeSteppers\Generated\Auth;

interface UserSaver
{
    public function save(NewUser $user): User;
}