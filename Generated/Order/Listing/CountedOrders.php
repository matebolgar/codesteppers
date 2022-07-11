<?php

  namespace CodeSteppers\Generated\Order\Listing;

  use CodeSteppers\Generated\Order\Order;

  class CountedOrders
  {
      /**
      * @var Order[]
      */
      private $entities;
 
      private $count;
  
      public function __construct(array $entities, int $count)
      {
          $this->entities = $entities;
          $this->count = $count;
      }

      public function getEntities(): array
      {
          return $this->entities;
      }
  
      public function getCount(): int
      {
          return $this->count;
      }
  }

  