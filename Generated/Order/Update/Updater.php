<?php
    namespace CodeSteppers\Generated\Order\Update;
    
    use CodeSteppers\Generated\Order\Order;
    
    interface Updater
    {
        function update(string $id, UpdatedOrder $order): Order;
    }
    