<?php
      namespace CodeSteppers\Generated\Order\Listing;
      
      use CodeSteppers\Generated\Listing\Query;
      
      interface Lister
      {
          function list(Query $query): CountedOrders;
      }
    