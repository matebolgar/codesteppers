<?php
    namespace CodeSteppers\Generated\Codestepper\Save;

    use CodeSteppers\Generated\Codestepper\Codestepper;

    interface Saver
    {
        function Save(NewCodestepper $new): Codestepper;
    }
    