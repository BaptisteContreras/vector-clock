<?php

namespace Dynamophp\VectorClock\Exception;

class NumericNodeNameException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Numeric value as  node name is not supported');
    }
}
