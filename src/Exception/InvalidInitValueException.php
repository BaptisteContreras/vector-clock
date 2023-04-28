<?php

namespace Dynamophp\VectorClock\Exception;

class InvalidInitValueException extends \Exception
{
    public function __construct(string $timestampClass)
    {
        parent::__construct(sprintf('The value of a %s must be >= 0', $timestampClass));
    }
}
