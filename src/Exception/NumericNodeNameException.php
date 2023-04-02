<?php

namespace Dynamophp\VectorClock\Exception;

class NumericNodeNameException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Numerics values are not supported for node name');
    }
}
