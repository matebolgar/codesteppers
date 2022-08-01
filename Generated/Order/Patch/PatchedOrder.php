<?php

namespace CodeSteppers\Generated\Order\Patch;

use JsonSerializable;

class PatchedOrder implements JsonSerializable
{
    private $status;
private $count;
private $totalCount;


    
public function __construct($status, $count, $totalCount)
{
        $this->status = $status;
$this->count = $count;
$this->totalCount = $totalCount;

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
    
    
    public function jsonSerialize()
    {
        return [
            'status' => $this->status,
 'count' => $this->count,
 'totalCount' => $this->totalCount,

        ];
    }
}
