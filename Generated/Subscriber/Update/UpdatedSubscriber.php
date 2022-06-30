<?php

namespace CodeSteppers\Generated\Subscriber\Update;

use JsonSerializable;

class UpdatedSubscriber implements JsonSerializable
{
    private $email;
private $password;
private $isVerified;
private $verificationToken;
private $isUnsubscribed;


    
public function __construct($email, $password, $isVerified, $verificationToken, $isUnsubscribed)
{
        $this->email = $email;
$this->password = $password;
$this->isVerified = $isVerified;
$this->verificationToken = $verificationToken;
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
 'isUnsubscribed' => $this->isUnsubscribed,

        ];
    }
}
