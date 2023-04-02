<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\AsyncVectorClock;
use Dynamophp\VectorClock\ClockOrder;
use PHPUnit\Framework\Attributes\DataProvider;

class AsyncVectorClockCompareTest extends AbstractAsyncVectorTest
{
    #[DataProvider('provideNotComparableClocks')]
    public function testNotComparable(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        self::assertEquals(ClockOrder::NOT_COMPARABLE, $clock1->compare($clock2));
        self::assertEquals(ClockOrder::NOT_COMPARABLE, $clock2->compare($clock1));
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testCanBeComparedWithReturnsFalse(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        self::assertFalse($clock1->canBeComparedWith($clock2));
        self::assertFalse($clock2->canBeComparedWith($clock1));
    }
}
