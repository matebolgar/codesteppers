<?php

namespace CodeSteppers\Generated\Order\Update;

use JsonSerializable;

class UpdatedOrder implements JsonSerializable
{
    private $status;


    
public function __construct($status)
{
        $this->status = $status;

}
    
    public function getStatus(): ?string
    {
        return $this->status;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'status' => $this->status,

        ];
    }
}
