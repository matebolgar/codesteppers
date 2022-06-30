<?php
    namespace CodeSteppers\Generated\Subscriber\Save;

    use CodeSteppers\Generated\Subscriber\Subscriber;

    interface Saver
    {
        function Save(NewSubscriber $new): Subscriber;
    }
    