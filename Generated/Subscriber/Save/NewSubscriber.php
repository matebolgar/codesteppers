<?php

namespace CodeSteppers\Generated\Subscriber\Save;

use JsonSerializable;

class NewSubscriber implements JsonSerializable
{
    private $email;
private $password;
private $isVerified;
private $verificationToken;
private $createdAt;
private $isUnsubscribed;


    
public function __construct($email, $password, $isVerified, $verificationToken, $createdAt, $isUnsubscribed)
{
        $this->email = $email;
$this->password = $password;
$this->isVerified = $isVerified;
$this->verificationToken = $verificationToken;
$this->createdAt = $createdAt;
$this->isUnsubscribed = $isUnsubscribed;

}
    
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function getPassword(): ?string
    {
        return $this->password;
    }
    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }
    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }
    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }
    public function getIsUnsubscribed(): ?bool
    {
        return $this->isUnsubscribed;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'email' => $this->email,
 'password' => $this->password,
 'isVerified' => $this->isVerified,
 'verificationToken' => $this->verificationToken,
 'createdAt' => $this->createdAt,
 'isUnsubscribed' => $this->isUnsubscribed,

        ];
    }
}
