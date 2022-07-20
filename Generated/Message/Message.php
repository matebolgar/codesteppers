<?php

namespace CodeSteppers\Generated\Message;

use JsonSerializable;

class Message implements JsonSerializable
{
    private $id;
private $email;
private $subject;
private $body;
private $status;
private $numberOfAttempts;
private $sentAt;
private $createdAt;


    
public function __construct($id, $email, $subject, $body, $status, $numberOfAttempts, $sentAt, $createdAt)
{
        $this->id = $id;
$this->email = $email;
$this->subject = $subject;
$this->body = $body;
$this->status = $status;
$this->numberOfAttempts = $numberOfAttempts;
$this->sentAt = $sentAt;
$this->createdAt = $createdAt;

}
    
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function getSubject(): ?string
    {
        return $this->subject;
    }
    public function getBody(): ?string
    {
        return $this->body;
    }
    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function getNumberOfAttempts(): ?int
    {
        return $this->numberOfAttempts;
    }
    public function getSentAt(): ?int
    {
        return $this->sentAt;
    }
    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
 'email' => $this->email,
 'subject' => $this->subject,
 'body' => $this->body,
 'status' => $this->status,
 'numberOfAttempts' => $this->numberOfAttempts,
 'sentAt' => $this->sentAt,
 'createdAt' => $this->createdAt,

        ];
    }
}
