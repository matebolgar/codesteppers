<?php
      namespace CodeSteppers\Generated\Message\Listing;
      
      use CodeSteppers\Generated\Listing\Query;
      
      interface Lister
      {
          function list(Query $query): CountedMessages;
      }
    