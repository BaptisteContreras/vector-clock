<?php

namespace Dynamophp\VectorClock\Exception;

class InvalidNodeNameException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Only string value are supported');
    }
}
