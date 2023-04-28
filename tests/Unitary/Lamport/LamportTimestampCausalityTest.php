<?php

namespace Dynamophp\VectorClock\Test\Unitary\Lamport;

use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\LamportTimestamp;
use PHPUnit\Framework\Attributes\DataProvider;

class LamportTimestampCausalityTest extends AbstractLamportTimestampTest
{
    #[DataProvider('provideCausalityCases')]
    public function testCompareCausality(LamportTimestamp $lt1, LamportTimestamp $lt2): void
    {
        // $lt1 -> $lt2 in all our cases
        self::assertEquals(ClockOrder::HAPPEN_BEFORE, $lt1->compare($lt2));
        self::assertEquals(ClockOrder::HAPPEN_AFTER, $lt2->compare($lt1));
    }

    #[DataProvider('provideCausalityCases')]
    public function testHappenBeforeAndAfter(LamportTimestamp $lt1, LamportTimestamp $lt2): void
    {
        // $lt1 -> $lt2 in all our cases
        self::assertTrue($lt1->happenBefore($lt2));
        self::assertFalse($lt1->happenAfter($lt2));

        self::assertTrue($lt2->happenAfter($lt1));
        self::assertFalse($lt2->happenBefore($lt1));
    }

    public static function provideCausalityCases(): \Generator
    {
        yield '[0] -> [1]' => [new LamportTimestamp(0), new LamportTimestamp(1)];
        yield '[0] -> [2]' => [new LamportTimestamp(0), new LamportTimestamp(2)];
        yield '[1] -> [2]' => [new LamportTimestamp(1), new LamportTimestamp(2)];
        yield '[9] -> [10]' => [new LamportTimestamp(9), new LamportTimestamp(10)];
        yield '[80] -> [100]' => [new LamportTimestamp(80), new LamportTimestamp(100)];
        yield '[700] -> [100000]' => [new LamportTimestamp(700), new LamportTimestamp(100000)];
    }
}
