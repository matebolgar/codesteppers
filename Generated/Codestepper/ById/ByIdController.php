<?php
    namespace CodeSteppers\Generated\Codestepper\ById;
    
    use Exception;
    use CodeSteppers\Generated\Codestepper\Error\Error;
    use CodeSteppers\Generated\Codestepper\Error\OperationError;
    use CodeSteppers\Generated\Codestepper\Codestepper;

    
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
    
        public function byId(string $id): Codestepper
        {
            try {
                return $this->byId->byId($id);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    }

  