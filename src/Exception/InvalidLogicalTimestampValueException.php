<?php

namespace Dynamophp\VectorClock\Exception;

use Dynamophp\VectorClock\LogicalTimestamp;

class InvalidLogicalTimestampValueException extends \Exception
{
    public function __construct()
    {
        parent::__construct(sprintf('The value of a %s must be >= 0', LogicalTimestamp::class));
    }
}
