<?php
    namespace CodeSteppers\Generated\Codestepper\Patch;
    
    use CodeSteppers\Generated\Codestepper\Codestepper;
    
    interface Patcher
    {
        function patch(string $id, PatchedCodestepper $codestepper): Codestepper;
    }
    