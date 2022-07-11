<?php
    namespace CodeSteppers\Generated\Order\Save;

    use CodeSteppers\Generated\Order\Order;

    interface Saver
    {
        function Save(NewOrder $new): Order;
    }
    