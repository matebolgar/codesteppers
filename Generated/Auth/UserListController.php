<?php

namespace CodeSteppers\Generated\Auth;

use Exception;

class UserListController
{
    private $userLister;
    private $tokenVerifier;

    public function __construct(UserLister $userLister, TokenVerifier $tokenVerifier)
    {
        $this->userLister = $userLister;
        $this->tokenVerifier = $tokenVerifier;
    }

    public function listUsers(string $token): array
    {
        try {
            $this->tokenVerifier->verify($token);
        } catch (ExpiredException $exception) {
            throw new ExpiredException();
        } catch (Exception $exception) {
            throw new AuthException();
        }

        return $this->userLister->listUsers();
    }
}
