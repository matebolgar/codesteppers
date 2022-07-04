<?php

    namespace CodeSteppers\Generated\Codestepper\Update;

    use Exception;
    use CodeSteppers\Generated\Codestepper\Error\Error;
    use CodeSteppers\Generated\Codestepper\Error\OperationError;
    use CodeSteppers\Generated\Codestepper\Error\ValidationError;
    use CodeSteppers\Generated\Codestepper\Update\Updater;
    use CodeSteppers\Generated\Codestepper\Codestepper;
    
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
    
        public function update(array $entity, string $id): Codestepper
        {    
            try {
                $toUpdate = new UpdatedCodestepper($entity['subscriberId'] ?? 0, $entity['guestId'] ?? '', $entity['title'] ?? '');
               
                return $this->updater->update($id, $toUpdate);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    
    }

  