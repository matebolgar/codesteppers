<?php
    namespace CodeSteppers\Generated\Message\ById;
    
    use Exception;
    use CodeSteppers\Generated\Message\Error\Error;
    use CodeSteppers\Generated\Message\Error\OperationError;
    use CodeSteppers\Generated\Message\Message;

    
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
    
        public function byId(string $id): Message
        {
            try {
                return $this->byId->byId($id);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    }

  