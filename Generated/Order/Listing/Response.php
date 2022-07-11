<?php

namespace CodeSteppers\Generated\Order\Listing;

use JsonSerializable;
use CodeSteppers\Generated\Listing\Paging;
use CodeSteppers\Generated\Order\Order;

class Response implements JsonSerializable
{
    private $paging;

    /**
     * @var Order[]
     */
    private $results;
    
    public function __construct(Paging $paging, array $results)
    {
        $this->paging = $paging;
        $this->results = $results;
    }


    public function getPaging(): Paging
    {
        return $this->paging;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function jsonSerialize()
    {
        return [
            'paging' => $this->paging,
            'results' => $this->results,
        ];
    }
}
  