<?php

namespace Dynamophp\VectorClock\Exception;

use Dynamophp\VectorClock\LogicalTimestamp;

class InvalidVectorClockStateException extends \Exception
{
    public function __construct()
    {
        parent::__construct(sprintf('The initial state require a string as key and a %s instance as a value', LogicalTimestamp::class));
    }
}
