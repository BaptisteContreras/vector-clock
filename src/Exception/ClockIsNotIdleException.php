<?php

namespace Dynamophp\VectorClock\Exception;

class ClockIsNotIdleException extends \Exception
{
    public function __construct()
    {
        parent::__construct('The clock is waiting to be sync'); // TODO rephrase this
    }
}
