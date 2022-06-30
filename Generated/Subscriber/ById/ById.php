<?php
    namespace CodeSteppers\Generated\Subscriber\ById;
    
    use CodeSteppers\Generated\Subscriber\Subscriber;
    
    interface ById
    {
        function byId(string $id): Subscriber;
    }
    