<?php

namespace Dynamophp\VectorClock\Exception;

class CannotReceiveSameLamportTimestampInstanceException extends \Exception
{
    public function __construct()
    {
        parent::__construct('The receiver and the received Lamport timestamps are the same object instance');
    }
}
