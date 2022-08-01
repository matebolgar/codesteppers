<?php

namespace CodeSteppers\Generated\Order;

use JsonSerializable;

class Order implements JsonSerializable
{
    private $id;
private $subscriberId;
private $plan;
private $ref;
private $status;
private $count;
private $totalCount;
private $createdAt;


    
public function __construct($id, $subscriberId, $plan, $ref, $status, $count, $totalCount, $createdAt)
{
        $this->id = $id;
$this->subscriberId = $subscriberId;
$this->plan = $plan;
$this->ref = $ref;
$this->status = $status;
$this->count = $count;
$this->totalCount = $totalCount;
$this->createdAt = $createdAt;

}
    
    public function getId(): ?int
    {
        return $this->id;
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
    public function getCount(): ?int
    {
        return $this->count;
    }
    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }
    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
 'subscriberId' => $this->subscriberId,
 'plan' => $this->plan,
 'ref' => $this->ref,
 'status' => $this->status,
 'count' => $this->count,
 'totalCount' => $this->totalCount,
 'createdAt' => $this->createdAt,

        ];
    }
}
