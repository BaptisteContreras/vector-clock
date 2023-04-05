<?php

namespace Dynamophp\VectorClock\Exception;

class UnknownNodeException extends \Exception
{
    public function __construct(string $node)
    {
        parent::__construct(sprintf('%s is not in the vector', $node));
    }
}
