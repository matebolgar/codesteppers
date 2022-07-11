<?php

    namespace CodeSteppers\Generated\Order\Update;

    use Exception;
    use CodeSteppers\Generated\Order\Error\Error;
    use CodeSteppers\Generated\Order\Error\OperationError;
    use CodeSteppers\Generated\Order\Error\ValidationError;
    use CodeSteppers\Generated\Order\Update\Updater;
    use CodeSteppers\Generated\Order\Order;
    
    class UpdateController
    {
        /**
         * @var Updater
         */
        private $updater;
    
        /**
         * @var OperationError
         */
        private $operationError;
    
        /**
         * @var ValidationError
         */
        private $requiredError;
    
        public function __construct(Updater $updater, OperationError $operationError, ValidationError $requiredError)
        {
            $this->updater = $updater;
            $this->operationError = $operationError;
            $this->requiredError = $requiredError;
        }
    
        public function update(array $entity, string $id): Order
        {    
            try {
                $toUpdate = new UpdatedOrder($entity['status'] ?? '');
               
                return $this->updater->update($id, $toUpdate);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    
    }

  