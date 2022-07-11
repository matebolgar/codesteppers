<?php
    namespace CodeSteppers\Generated\Order\Patch;
    
    use CodeSteppers\Generated\Order\Order;
    
    interface Patcher
    {
        function patch(string $id, PatchedOrder $order): Order;
    }
    