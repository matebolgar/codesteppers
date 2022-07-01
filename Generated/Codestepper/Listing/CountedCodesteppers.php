<?php

  namespace CodeSteppers\Generated\Codestepper\Listing;

  use CodeSteppers\Generated\Codestepper\Codestepper;

  class CountedCodesteppers
  {
      /**
      * @var Codestepper[]
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

  