<?php

namespace Dynamophp\VectorClock\Test\Unitary\Timestamp;

use Dynamophp\VectorClock\Exception\InvalidInitValueException;
use Dynamophp\VectorClock\LogicalTimestamp;
use PHPUnit\Framework\TestCase;

class LogicalTimestampTest extends TestCase
{
    public function testCreationWithNegativeValue(): void
    {
        $this->expectException(InvalidInitValueException::class);

        new LogicalTimestamp(-1);
    }
}
