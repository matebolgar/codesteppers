<?php

namespace CodeSteppers\Generated\Repository\Order;

use CodeSteppers\Generated\Order\Listing\CountedOrders;
use CodeSteppers\Generated\Order\Listing\Lister;
use CodeSteppers\Generated\Order\Order;
use CodeSteppers\Generated\Listing\Filter;
use CodeSteppers\Generated\Listing\Clause;
use CodeSteppers\Generated\Listing\Query;
use CodeSteppers\Generated\OperationError;
use mysqli;

class SqlLister implements Lister
{
    private $connection;

    const OPERATOR_MAP = [
        'eq' => '=',
        'neq' => '!=',
        'lt' => '<',
        'gt' => '>',
        'lte' => '<=',
        'gte' => '>=',
        'in' => 'IN',
        'nin' => 'NOT IN',
        'like' => "LIKE"
    ];

    const ORDER_BY_MAP = [
        'asc' => 'ASC',
        'desc' => 'DESC',
    ];

    const RELATION_MAP = [
        'and' => 'AND',
        'or' => 'OR',
        'not' => 'AND NOT'
    ];

    const TYPE_MAP = [
        'string' => 's',
        'integer' => 'i',
        'boolean' => 'b',
    ];

    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    public function list(Query $query): CountedOrders
    {
        try {
            $select = $this->getSelectQuery($query);
            
            $stmt = $this->connection->prepare($select(empty($query->getColumns()) ?
                '*' :
                implode(',', $query->getColumns())
            ));
            
            $stmt = $this->connection->prepare($select('*'));
    
            if (!$stmt) { 
                return new CountedOrders([], 0);
            }
    
            $filterValues = $query->getFilter() ? $this->getFilterValues($query->getFilter(), []) : [];

            call_user_func_array(function (...$params) use ($stmt) {
                $types = array_reduce(
                    array_map(function ($param) {
                        return self::TYPE_MAP[gettype($param)];
                    }, $params),
                    $this->getSum(),
                    ''
                );
                $stmt->bind_param($types, ...$params);
            }, array_merge(
                    $this->flatten($filterValues),
                    [$query->getLimit(), $query->getOffset()]
                )
            );

            $stmt->execute();
            $result = $stmt->get_result();

            $results = [];
            while ($data = $result->fetch_assoc()) {
                $results[] = $data;
            }

            $countStmt = $this->connection->prepare($this->getCountQuery($query)('COUNT(*)'));

            call_user_func_array(function (...$params) use ($countStmt) {
                $types = array_reduce(
                    array_map(function ($param) {
                        return self::TYPE_MAP[gettype($param)];
                    }, $params),
                    $this->getSum(),
                    ''
                );

                if (!empty($params)) {
                    $countStmt->bind_param($types, ...$params);
                }
            }, $this->flatten($filterValues));

            $countStmt->execute();

            $count = $countStmt->get_result()->fetch_array()['COUNT(*)'] ?? 0;
    
            return new CountedOrders(
                array_map(function ($array) {
                    return new Order((int)$array['id'], (int)$array['subscriberId'], (string)$array['plan'], (string)$array['ref'], (string)$array['status'], (int)$array['count'], (int)$array['totalCount'], (int)$array['createdAt']);
                }, $results),
                $count
            );
        
        } catch (\Error $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
            throw new OperationError("list error");
        } catch (\Exception $exception) {
            if ($_SERVER['DEPLOYMENT_ENV'] === 'dev') {
                var_dump($exception);
                exit;
            }
            throw new OperationError("list error");
        }
    }

    private function getSelectQuery(Query $query): callable
    {
        return function ($cols) use ($query) {
            $vals = $query->getFilter() ? $this->getFilter($query->getFilter(), '') : '';

            return sprintf(
                "SELECT %s FROM `orders` %s %s %s LIMIT ? OFFSET ?",
                $cols,
                $vals === '' ? '' : 'WHERE',
                $vals,
                $query->getOrderBy()->getValue()
                    ? sprintf('ORDER BY %s %s', $query->getOrderBy()->getKey(), self::ORDER_BY_MAP[$query->getOrderBy()->getValue()])
                    : ''
            );
        };
    }
    
    private function getCountQuery(Query $query): callable
    {
        return function ($cols) use ($query) {
            $vals = $query->getFilter() ? $this->getFilter($query->getFilter(), '') : '';

            return sprintf(
                "SELECT %s FROM `orders` %s %s %s",
                $cols,
                $vals === '' ? '' : 'WHERE',
                $vals,
                $query->getOrderBy()->getValue()
                    ? sprintf('ORDER BY %s %s', $query->getOrderBy()->getKey(), self::ORDER_BY_MAP[$query->getOrderBy()->getValue()])
                    : ''
            );
        };
    }

    /**
     * @param $filter Filter | Clause
     * @param string $query
     * @return string
     */
    private function getFilter($filter, string $query)
    {
        if ($filter instanceof Clause) {
            if ($filter->getOperator() === 'in' || $filter->getOperator() === 'nin') {
                return sprintf(
                    "%s %s %s",
                    $filter->getKey(),
                    self::OPERATOR_MAP[$filter->getOperator()],
                    sprintf('(%s)', implode(', ', array_map(function ($value) {
                        return "?";
                    }, $filter->getValue())))
                );
            }

            return $query . sprintf("%s %s %s", $filter->getKey(), self::OPERATOR_MAP[$filter->getOperator()], '?');
        }

        return sprintf(
            $filter->getRelation() === 'or' ? "(%s %s %s)" : "%s %s %s",
            $this->getFilter($filter->getLeft(), $query),
            self::RELATION_MAP[$filter->getRelation()],
            $this->getFilter($filter->getRight(), $query)
        );
    }

    /**
     * @param $filter Filter | Clause
     * @param array $values
     * @return array
     */
    private function getFilterValues($filter, array $values)
    {
        if ($filter instanceof Clause) {
            if ($filter->getOperator() === 'like') {
                return ["%" . $filter->getValue() . "%"];
            }
            return [$filter->getValue()];
        }

        return array_merge(
            $this->getFilterValues($filter->getLeft(), $values),
            $this->getFilterValues($filter->getRight(), $values)
        );
    }

    private function flatten(array $array)
    {
        $return = [];
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }

    private function getSum()
    {
        return function ($acc, $cr) {
            return $acc . $cr;
        };
    }
}

