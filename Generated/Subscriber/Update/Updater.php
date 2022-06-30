<?php
    namespace CodeSteppers\Generated\Subscriber\Update;
    
    use CodeSteppers\Generated\Subscriber\Subscriber;
    
    interface Updater
    {
        function update(string $id, UpdatedSubscriber $subscriber): Subscriber;
    }
    