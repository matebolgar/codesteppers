<?php

namespace CodeSteppers\Generated\Auth;

use JsonSerializable;

class PublicUser implements JsonSerializable
{
    private $id;
    private $email;

    public function __construct(string $id, string $email)
    {
        $this->id = $id;
        $this->email = $email;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
        ];
    }
}
