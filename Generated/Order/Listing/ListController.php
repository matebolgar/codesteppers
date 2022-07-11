<?php

namespace CodeSteppers\Generated\Order\Listing;

use CodeSteppers\Generated\Listing\Clause;
use CodeSteppers\Generated\Listing\Filter;
use CodeSteppers\Generated\Listing\OrderBy;
use CodeSteppers\Generated\Listing\Query;
use CodeSteppers\Generated\Listing\Pager;
use CodeSteppers\Generated\Listing\Links;
use CodeSteppers\Generated\Listing\Paging;
use CodeSteppers\Generated\Order\Error\Error;
use CodeSteppers\Generated\Order\Error\OperationError;

use Exception;

class ListController
{
    /**
     * @var OperationError
     */
    private $operationError;

    /**
     * @var Lister
     */
    private $lister;

    /**
     * @var Pager
     */
    private $pager;

    public function __construct(OperationError $operationError, Lister $lister, Pager $pager)
    {
        $this->operationError = $operationError;
        $this->lister = $lister;
        $this->pager = $pager;
    }

    public function list(array $rawQuery): Response
    {
        try {
            $query = new Query(
                $rawQuery['limit'],
                $rawQuery['from'],
                !empty($rawQuery['filters']) ? $this->getFilters($rawQuery['filters']): null,
                new OrderBy($rawQuery['orderBy']['key'] ?? '', $rawQuery['orderBy']['value'] ?? '')
            );

            $countedList = $this->lister->list($query);
            $paging = $this->pager->getPaging($query->getLimit(), $query->getOffset(), '', $countedList->getCount());

            return new Response(
                new Paging(
                    new Links(
                        $paging["links"]['first'] ?? '',
                        $paging["links"]['prev'] ?? '',
                        $paging["links"]['next'] ?? '',
                        $paging["links"]['current'] ?? '',
                        $paging["links"]['last'] ?? ''
                    ),
                    $paging['count'],
                    $countedList->getCount()
                ),
                $countedList->getEntities()
            );
        } catch (Exception $err) {
            $error = $this->operationError;
            $error->addField(Error::getOperationError());
            throw $error;
        }
    }

    /**
     * @param array $query
     * @return Clause|Filter
     */
    private function getFilters(array $query)
    {
        if (isset($query['operator'])) {
            return new Clause($query['operator'], $query['key'], $query['value']);
        }
        return new Filter(
            $query['relation'] ?? [],
            $query['left']['relation'] ?? []
                ? $this->getFilters($query['left'])
                : new Clause($query['left']['operator'], $query['left']['key'], $query['left']['value']),
            $query['right']['relation'] ?? []
                ? $this->getFilters($query['right'])
                : new Clause($query['right']['operator'], $query['right']['key'], $query['right']['value'])

        );
    }
}
  