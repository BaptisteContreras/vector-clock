<?php

namespace Dynamophp\VectorClock\Test\Unitary\Lamport;

use Dynamophp\VectorClock\LamportTimestamp;

class LamportTimestampSendEventTest extends AbstractLamportTimestampTest
{
    public function testApplySendEvent(): void
    {
        $lt = new LamportTimestamp(0);
        self::assertTimestampValue(0, $lt);

        $lt->applySendEvent();
        self::assertTimestampValue(1, $lt);

        $lt->applySendEvent();
        self::assertTimestampValue(2, $lt);

        $lt->applySendEvent();
        $lt->applySendEvent();
        $lt->applySendEvent();
        $lt->applySendEvent();
        $lt->applySendEvent();
        $lt->applySendEvent();
        $lt->applySendEvent();
        $lt->applySendEvent();

        self::assertTimestampValue(10, $lt);
    }
}
