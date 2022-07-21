<?php

namespace CodeSteppers\Generated\Order\Update;

use JsonSerializable;

class UpdatedOrder implements JsonSerializable
{
    private $status;
private $count;


    
public function __construct($status, $count)
{
        $this->status = $status;
$this->count = $count;

}
    
    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function getCount(): ?int
    {
        return $this->count;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'status' => $this->status,
 'count' => $this->count,

        ];
    }
}
