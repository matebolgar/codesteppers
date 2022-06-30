<?php

namespace CodeSteppers\Generated\Listing;

class Filter
{
    const RELATIONS = ['and', 'or'];

    /**
     * @var string
     */
    private $relation;
    /**
     * @var Clause | Filter
     */
    private $left;

    /**
     * @var Clause | Filter
     */
    private $right;

    /**
     * Filter constructor.
     * @param string $relation
     * @param Filter|Clause $left
     * @param Filter|Clause $right
     */
    public function __construct($relation, $left, $right)
    {
        $this->left = $left;
        $this->right = $right;
        $this->relation = in_array($relation, self::RELATIONS) ? $relation : 'and';
    }

    /**
     * @return string
     */
    public function getRelation(): string
    {
        return $this->relation;
    }

    /**
     * @return Clause|Filter
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @return Clause|Filter
     */
    public function getRight()
    {
        return $this->right;
    }
}

  