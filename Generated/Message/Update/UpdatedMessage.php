<?php

namespace CodeSteppers\Generated\Message\Update;

use JsonSerializable;

class UpdatedMessage implements JsonSerializable
{
    private $status;
private $numberOfAttempts;
private $sentAt;


    
public function __construct($status, $numberOfAttempts, $sentAt)
{
        $this->status = $status;
$this->numberOfAttempts = $numberOfAttempts;
$this->sentAt = $sentAt;

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
    
    
    public function jsonSerialize()
    {
        return [
            'status' => $this->status,
 'numberOfAttempts' => $this->numberOfAttempts,
 'sentAt' => $this->sentAt,

        ];
    }
}
