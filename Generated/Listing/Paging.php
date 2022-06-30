<?php

namespace CodeSteppers\Generated\Listing;

use JsonSerializable;

class Paging implements JsonSerializable
{
    private $links;
    private $count;
    private $total;

    public function __construct(Links $links, int $count, int $total)
    {
        $this->links = $links;
        $this->count = $count;
        $this->total = $total;
    }

    public function getLinks(): Links
    {
        return $this->links;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function jsonSerialize()
    {
        return [
            'count' => $this->count,
            'links' => $this->links,
            'total' => $this->total,
        ];
    }
}

  