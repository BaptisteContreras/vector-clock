<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync;

use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\SyncVectorClock;
use PHPUnit\Framework\Attributes\DataProvider;

class SyncVectorClockCompareTest extends AbstractSyncVectorTest
{
    #[DataProvider('provideNotComparableClocks')]
    public function testNotComparable(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        self::assertEquals(ClockOrder::NOT_COMPARABLE, $clock1->compare($clock2));
        self::assertEquals(ClockOrder::NOT_COMPARABLE, $clock2->compare($clock1));
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testCanBeComparedWithReturnsFalse(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        self::assertFalse($clock1->canBeComparedWith($clock2));
        self::assertFalse($clock2->canBeComparedWith($clock1));
    }
}
