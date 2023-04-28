<?php

namespace Dynamophp\VectorClock\Test\Unitary\Lamport;

use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\LamportTimestamp;
use PHPUnit\Framework\Attributes\DataProvider;

class LamportTimestampIdenticalTest extends AbstractLamportTimestampTest
{
    #[DataProvider('provideIdenticalCases')]
    public function testCompareIdentical(LamportTimestamp $lt1, LamportTimestamp $lt2): void
    {
        // $lt1 == $lt2 in all our cases
        self::assertEquals(ClockOrder::IDENTICAL, $lt1->compare($lt2));
        self::assertEquals(ClockOrder::IDENTICAL, $lt2->compare($lt1));
    }

    #[DataProvider('provideIdenticalCases')]
    public function testIsIdentical(LamportTimestamp $lt1, LamportTimestamp $lt2): void
    {
        // $lt1 == $lt2 in all our cases
        self::assertTrue($lt1->isIdenticalTo($lt2));
        self::assertTrue($lt2->isIdenticalTo($lt1));
    }

    public static function provideIdenticalCases(): \Generator
    {
        yield '[0] == [0]' => [new LamportTimestamp(0), new LamportTimestamp(0)];
        yield '[1] == [1]' => [new LamportTimestamp(1), new LamportTimestamp(1)];
        yield '[2] == [2]' => [new LamportTimestamp(2), new LamportTimestamp(2)];
        yield '[10] == [10]' => [new LamportTimestamp(10), new LamportTimestamp(10)];
        yield '[100] == [100]' => [new LamportTimestamp(100), new LamportTimestamp(100)];
        yield '[999] == [999]' => [new LamportTimestamp(999), new LamportTimestamp(999)];
        yield '[1000000000] == [1000000000]' => [new LamportTimestamp(1000000000), new LamportTimestamp(1000000000)];
    }
}
