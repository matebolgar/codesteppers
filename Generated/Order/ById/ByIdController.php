<?php
    namespace CodeSteppers\Generated\Order\ById;
    
    use Exception;
    use CodeSteppers\Generated\Order\Error\Error;
    use CodeSteppers\Generated\Order\Error\OperationError;
    use CodeSteppers\Generated\Order\Order;

    
    class ByIdController
    {
        /**
         * @var ById
         */
        private $byId;
    
        /**
         * @var OperationError
         */
        private $operationError;
    
        public function __construct(ById $byId, OperationError $operationError)
        {
            $this->byId = $byId;
            $this->operationError = $operationError;
        }
    
        public function byId(string $id): Order
        {
            try {
                return $this->byId->byId($id);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    }

  