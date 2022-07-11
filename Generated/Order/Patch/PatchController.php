<?php

    namespace CodeSteppers\Generated\Order\Patch;

    use Exception;
    use CodeSteppers\Generated\Order\Error\Error;
    use CodeSteppers\Generated\Order\Error\OperationError;
    use CodeSteppers\Generated\Order\Error\ValidationError;
    use CodeSteppers\Generated\Order\Order;
      
    class PatchController
    {
        /**
         * @var Patcher
         */
        private $patcher;
    
        /**
         * @var OperationError
         */
        private $operationError;
    
        /**
         * @var ValidationError
         */
        private $requiredError;
    
        public function __construct(Patcher $updater, OperationError $operationError, ValidationError $requiredError)
        {
            $this->patcher = $updater;
            $this->operationError = $operationError;
            $this->requiredError = $requiredError;
        }
    
        public function patch(array $entity, string $id): Order
        {
            try {
                @$toPatch = new PatchedOrder($entity['status'] ?? null);
                return $this->patcher->patch($id, $toPatch);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    }

  