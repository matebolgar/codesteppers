<?php

namespace CodeSteppers\Generated\Order\Save;

use JsonSerializable;

class NewOrder implements JsonSerializable
{
    private $subscriberId;
private $plan;
private $ref;
private $status;
private $createdAt;


    
public function __construct($subscriberId, $plan, $ref, $status, $createdAt)
{
        $this->subscriberId = $subscriberId;
$this->plan = $plan;
$this->ref = $ref;
$this->status = $status;
$this->createdAt = $createdAt;

}
    
    public function getSubscriberId(): ?int
    {
        return $this->subscriberId;
    }
    public function getPlan(): ?string
    {
        return $this->plan;
    }
    public function getRef(): ?string
    {
        return $this->ref;
    }
    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'subscriberId' => $this->subscriberId,
 'plan' => $this->plan,
 'ref' => $this->ref,
 'status' => $this->status,
 'createdAt' => $this->createdAt,

        ];
    }
}
