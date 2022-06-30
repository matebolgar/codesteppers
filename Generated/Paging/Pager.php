<?php

namespace CodeSteppers\Generated\Paging;

use Exception;
use CodeSteppers\Generated\Listing\Pager as IPager;

class Pager implements IPager
{
    const LIMIT_MAX = 1000;

    public function getPaging(int $limit, int $offset, string $path, int $total): array
    {

        if (!$this->isInputValid($limit, $offset)) {
            throw new Exception("invalid input");
        }

        return [
            "links" => $this->getLinks($limit, $offset, $path, $total),
            "offset" => $offset,
            "count" => $this->getCount($limit, $offset, $total),
            "total" => $total,
            "limit" => $limit,
        ];
    }

    private function getLinks(int $limit, int $offset, string $path, int $total): array
    {
        $prev = $this->getPreviousOffset($limit, $offset);
        $next = $this->getNextOffset($limit, $offset, $total);

        $lastOffset = $total - $limit < 0 ? 0 : $total - $limit;

        return [
            "first" => $path . "?limit=$limit&from=0",
            "next" => $offset + $limit >= $total ? '' : $path . "?limit=$limit&from=$next",
            "prev" => $prev < 0 ? '' : $path . "?limit=$limit&from=$prev",
            "current" => $path . "?limit=$limit&from=$offset",
            "last" => $path . "?limit=$limit&from=$lastOffset",
        ];
    }

    private function isInputValid(int $limit, int $offset): bool
    {
        if ($offset < 0) {
            return false;
        }

        if ($limit <= 0 || $limit > $this::LIMIT_MAX) {
            return false;
        }
        return true;
    }


    private function getCount(int $limit, int $offset, int $total): int
    {
        if ($total - $offset > $limit) {
            return $limit;
        }
        $c = $total - $offset;
        if ($c < 0) {
            return 0;
        }
        return $c;
    }

    private function getPreviousOffset(int $limit, int $offset): int
    {
        $prev = $offset - $limit;
        if ($prev < 0) {
            return -1;
        }

        return $prev;
    }

    private function getNextOffset(int $limit, int $offset, int $total): int
    {
        $next = $offset + $limit;
        if ($next > $total) {
            return $total;
        }
        return $next;
    }
}

