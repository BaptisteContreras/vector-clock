<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\InvalidNodeNameException;
use Dynamophp\VectorClock\Exception\InvalidVectorClockStateException;
use Dynamophp\VectorClock\Exception\NumericNodeNameException;

final class VectorClockFactory
{
    /**
     * @throws InvalidNodeNameException
     * @throws InvalidVectorClockStateException
     * @throws NumericNodeNameException
     */
    public static function createAsyncClock(string $node): AsyncVectorClock
    {
        return new AsyncVectorClock($node);
    }
}
