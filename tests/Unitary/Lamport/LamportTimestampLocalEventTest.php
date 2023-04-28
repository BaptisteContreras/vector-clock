<?php

namespace Dynamophp\VectorClock\Test\Unitary\Lamport;

use Dynamophp\VectorClock\LamportTimestamp;

class LamportTimestampLocalEventTest extends AbstractLamportTimestampTest
{
    public function testApplyLocalEvent(): void
    {
        $lt = new LamportTimestamp(0);
        self::assertTimestampValue(0, $lt);

        $lt->applyLocalEvent();
        self::assertTimestampValue(1, $lt);

        $lt->applyLocalEvent();
        self::assertTimestampValue(2, $lt);

        $lt->applyLocalEvent();
        $lt->applyLocalEvent();
        $lt->applyLocalEvent();
        $lt->applyLocalEvent();
        $lt->applyLocalEvent();
        $lt->applyLocalEvent();
        $lt->applyLocalEvent();
        $lt->applyLocalEvent();

        self::assertTimestampValue(10, $lt);
    }
}
