<?php
    namespace CodeSteppers\Generated\Message\Save;

    use CodeSteppers\Generated\Message\Message;

    interface Saver
    {
        function Save(NewMessage $new): Message;
    }
    