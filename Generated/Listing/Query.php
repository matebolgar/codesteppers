<?php

namespace CodeSteppers\Generated\Listing;

class Query
{
    private $limit;
    private $offset;
    private $filter;
    private $orderBy;
    private $columns;

    /**
     * Query constructor.
     * @param int $limit
     * @param int $offset
     * @param $filter Clause | Filter
     * @param OrderBy $orderBy
     * @param $columns
     */
    public function __construct(int $limit, int $offset, $filter, OrderBy $orderBy, $columns = [])
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->filter = $filter;
        $this->orderBy = $orderBy;
        $this->columns = $columns;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return Clause | Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    public function getOrderBy(): OrderBy
    {
        return $this->orderBy;
    }
    
    public function getColumns(): array
    {
        return $this->columns ?? [];
    }
}

  