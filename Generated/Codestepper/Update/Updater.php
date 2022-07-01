<?php
    namespace CodeSteppers\Generated\Codestepper\Update;
    
    use CodeSteppers\Generated\Codestepper\Codestepper;
    
    interface Updater
    {
        function update(string $id, UpdatedCodestepper $codestepper): Codestepper;
    }
    