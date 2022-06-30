<?php
    namespace CodeSteppers\Generated\Subscriber\Save;

    interface Slugifier
    {
        public function slugify(string $item): string;
    }
    