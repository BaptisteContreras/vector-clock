<?php

namespace Dynamophp\VectorClock\Exception;

class UnComparableException extends \Exception
{
    public function __construct()
    {
        parent::__construct('These clocks are not comparable');
    }
}
