<?php

namespace Dynamophp\VectorClock\Exception;

class ClockIsNotIdleException extends \Exception
{
    public function __construct()
    {
        parent::__construct('The clock is in communication with another clock');
    }
}
