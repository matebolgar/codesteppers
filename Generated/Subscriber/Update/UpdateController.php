<?php

    namespace CodeSteppers\Generated\Subscriber\Update;

    use Exception;
    use CodeSteppers\Generated\Subscriber\Error\Error;
    use CodeSteppers\Generated\Subscriber\Error\OperationError;
    use CodeSteppers\Generated\Subscriber\Error\ValidationError;
    use CodeSteppers\Generated\Subscriber\Update\Updater;
    use CodeSteppers\Generated\Subscriber\Subscriber;
    
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
    
        public function update(array $entity, string $id): Subscriber
        {    
            try {
                $toUpdate = new UpdatedSubscriber($entity['email'] ?? '', $entity['password'] ?? '', $entity['isVerified'] ?? false, $entity['verificationToken'] ?? '', $entity['isUnsubscribed'] ?? false);
               
                return $this->updater->update($id, $toUpdate);
            } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
            }
        }
    
    }

  