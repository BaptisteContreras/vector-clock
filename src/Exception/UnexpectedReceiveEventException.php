<?php

namespace Dynamophp\VectorClock\Exception;

class UnexpectedReceiveEventException extends \Exception
{
    public function __construct(string $expectedNode, string $receiveNode)
    {
        parent::__construct(sprintf('Expected a receive event from %s, got one from %s', $expectedNode, $receiveNode));
    }
}
