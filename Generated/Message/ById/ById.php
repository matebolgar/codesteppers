<?php
    namespace CodeSteppers\Generated\Message\ById;
    
    use CodeSteppers\Generated\Message\Message;
    
    interface ById
    {
        function byId(string $id): Message;
    }
    