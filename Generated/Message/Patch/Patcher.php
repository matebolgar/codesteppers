<?php
    namespace CodeSteppers\Generated\Message\Patch;
    
    use CodeSteppers\Generated\Message\Message;
    
    interface Patcher
    {
        function patch(string $id, PatchedMessage $message): Message;
    }
    