<?php
    namespace CodeSteppers\Generated\Order\ById;
    
    use CodeSteppers\Generated\Order\Order;
    
    interface ById
    {
        function byId(string $id): Order;
    }
    