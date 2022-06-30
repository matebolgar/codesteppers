<?php
    namespace CodeSteppers\Generated\Subscriber\Patch;
    
    use CodeSteppers\Generated\Subscriber\Subscriber;
    
    interface Patcher
    {
        function patch(string $id, PatchedSubscriber $subscriber): Subscriber;
    }
    