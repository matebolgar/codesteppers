<?php
    namespace CodeSteppers\Generated\Message\Update;
    
    use CodeSteppers\Generated\Message\Message;
    
    interface Updater
    {
        function update(string $id, UpdatedMessage $message): Message;
    }
    