<?php

    namespace CodeSteppers\Generated\Message\Patch;

    use Exception;
    use CodeSteppers\Generated\Message\Error\Error;
    use CodeSteppers\Generated\Message\Error\OperationError;
    use CodeSteppers\Generated\Message\Error\ValidationError;
    use CodeSteppers\Generated\Message\Message;
      
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
    
        public function patch(array $entity, string $id): Message
        {
            try {
                @$toPatch = new PatchedMessage($entity['status'] ?? null, $entity['numberOfAttempts'] ?? null, $entity['sentAt'] ?? null);
                return $this->patcher->patch($id, $toPatch);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    }

  