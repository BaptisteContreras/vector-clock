<?php

namespace Dynamophp\VectorClock\Test\Unitary\Lamport;

use Dynamophp\VectorClock\LamportTimestamp;
use PHPUnit\Framework\TestCase;

abstract class AbstractLamportTimestampTest extends TestCase
{
    protected static function assertTimestampValue(int $expectedValue, LamportTimestamp $lt): void
    {
        self::assertEquals($expectedValue, $lt->getValue());
    }
}
