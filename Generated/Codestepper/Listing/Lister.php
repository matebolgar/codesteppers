<?php
      namespace CodeSteppers\Generated\Codestepper\Listing;
      
      use CodeSteppers\Generated\Listing\Query;
      
      interface Lister
      {
          function list(Query $query): CountedCodesteppers;
      }
    