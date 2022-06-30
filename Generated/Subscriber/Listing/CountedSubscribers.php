<?php

  namespace CodeSteppers\Generated\Subscriber\Listing;

  use CodeSteppers\Generated\Subscriber\Subscriber;

  class CountedSubscribers
  {
      /**
      * @var Subscriber[]
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

  