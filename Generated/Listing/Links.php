<?php

namespace CodeSteppers\Generated\Listing;

use JsonSerializable;

class Links implements JsonSerializable
{
    private $first;
    private $prev;
    private $next;
    private $current;
    private $last;

    public function __construct(string $first, string $prev, string $next, string $current, string $last)
    {
        $this->first = $first;
        $this->prev = $prev;
        $this->next = $next;
        $this->current = $current;
        $this->last = $last;
    }

    public function getFirst(): string
    {
        return $this->first;
    }

    public function getPrev(): string
    {
        return $this->prev;
    }

    public function getNext(): string
    {
        return $this->next;
    }

    public function getCurrent(): string
    {
        return $this->current;
    }

    public function getLast(): string
    {
        return $this->last;
    }

    public function jsonSerialize()
    {

        return [
            'first' => $this->first,
            'prev' => $this->prev,
            'next' => $this->next,
            'current' => $this->current,
            'last' => $this->last,
        ];
    }
}

  