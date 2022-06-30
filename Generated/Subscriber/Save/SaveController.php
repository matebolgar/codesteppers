<?php

  namespace CodeSteppers\Generated\Subscriber\Save;

  use Exception;
  use CodeSteppers\Generated\Subscriber\Error\Error;
  use CodeSteppers\Generated\Subscriber\Error\OperationError;
  use CodeSteppers\Generated\Subscriber\Error\ValidationError;
  use CodeSteppers\Generated\Subscriber\Listing\Lister;
  use CodeSteppers\Generated\Subscriber\Subscriber;
  use CodeSteppers\Generated\Listing\Clause;
  use CodeSteppers\Generated\Listing\Filter;
  use CodeSteppers\Generated\Listing\OrderBy;
  use CodeSteppers\Generated\Listing\Query;

  class SaveController
{
    private $saver;

    private $lister;

    private $validationError;

    private $operationError;

    public function __construct(Saver $saver, Lister $lister, ValidationError $validationError, OperationError $operationError, Slugifier $slugifier)
    {
        $this->saver = $saver;
        $this->validationError = $validationError;
        $this->operationError = $operationError;
        $this->slugifier = $slugifier;
        $this->lister = $lister;
    }

    public function save(array $entity): Subscriber
    {
        $missing = array_map(function ($fieldName) {
            return Error::getValidationError($fieldName);
        }, array_filter(['email'], function ($fieldName) use ($entity) {
            return empty($entity[$fieldName]);
        }));

        $entity['createdAt'] = (new \DateTime())->getTimestamp();


        $notUnique = array_map(function ($fieldName) {
            return Error::getNotUniqueError($fieldName);
        }, array_filter(['email'], function ($fieldName) use ($entity) {
            return !empty($this->lister
                ->list(new Query(1, 0, new Clause('eq', $fieldName, $entity[$fieldName] ?? ''), new OrderBy('', '')))
                ->getEntities());
        }));

        //  $type = array_map(function ($keyValue) {
        //     return Error::getTypeError($keyValue[0]);
        // }, array_filter($this->toKeyValue($entity), function ($keyValue) {
        //     return !call_user_func($this->getTypeValidatorFn($keyValue[0]), $keyValue[1]);
        // }));

        $errors = array_merge($notUnique, $missing);

        if (count($errors) > 0) {
            $this->validationError->addErrors($errors);
            throw $this->validationError;
        }

        try {
          $toSave = new NewSubscriber((string)($entity['email']), (string)($entity['password'] ?? ''), (bool)($entity['isVerified'] ?? false), (string)($entity['verificationToken'] ?? ''), (int)($entity['createdAt'] ?? 0), (bool)($entity['isUnsubscribed'] ?? false));
              return $this->saver->Save($toSave);
        } catch (Exception $err) {
                $this->operationError->addField(Error::getOperationError());
                throw $this->operationError;
        }
    }

    private function toKeyValue(array $array)
    {
        return array_map(function ($key, $value) {
            return [$key, $value];
        }, array_keys($array), $array);
    }

    private function getTypeValidatorFn($key)
    {
        $validators = [
            'email' => [$this, 'isString'],
            'password' => [$this, 'isString'],
            'isVerified' => [$this, 'isBool'],
            'verificationToken' => [$this, 'isString'],
            'createdAt' => [$this, 'isInt'],
            'isUnsubscribed' => [$this, 'isBool']

        ];
        if (!array_key_exists($key, $validators)) {
            return function ($val) {
                return true;
            };
        }
        return $validators[$key];
    }

    private function isString($val): bool
    {
        return is_string($val);
    }

    private function isInt($val): bool
    {
        return is_int($val);
    }

    private function isBool($val): bool
    {
        return is_bool($val);
    }

    private function isJson($val): bool
    {
        json_decode($val);
        return (json_last_error() == JSON_ERROR_NONE);
    }

  }

