<?php

namespace Dynamophp\VectorClock\Exception;

class CannotReceiveSameClockInstanceException extends \Exception
{
    public function __construct()
    {
        parent::__construct('The receiver and the received clocks are the same object instance');
    }
}
