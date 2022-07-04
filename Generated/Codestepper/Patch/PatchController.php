<?php

    namespace CodeSteppers\Generated\Codestepper\Patch;

    use Exception;
    use CodeSteppers\Generated\Codestepper\Error\Error;
    use CodeSteppers\Generated\Codestepper\Error\OperationError;
    use CodeSteppers\Generated\Codestepper\Error\ValidationError;
    use CodeSteppers\Generated\Codestepper\Codestepper;
      
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
    
        public function patch(array $entity, string $id): Codestepper
        {
            try {
                @$toPatch = new PatchedCodestepper($entity['subscriberId'] ?? null, $entity['guestId'] ?? null, $entity['title'] ?? null);
                return $this->patcher->patch($id, $toPatch);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    }

  