<?php
      namespace CodeSteppers\Generated\Subscriber\Listing;
      
      use CodeSteppers\Generated\Listing\Query;
      
      interface Lister
      {
          function list(Query $query): CountedSubscribers;
      }
    