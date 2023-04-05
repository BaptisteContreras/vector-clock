<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\Exception\UnComparableException;
use Dynamophp\VectorClock\LogicalTimestamp;
use Dynamophp\VectorClock\SyncVectorClock;
use Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync\AbstractSyncVectorTest;
use PHPUnit\Framework\Attributes\DataProvider;

class SyncVectorClockCausalityTest extends AbstractSyncVectorTest
{
    #[DataProvider('provideCausalityCases')]
    public function testCompareCausality(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // $clock1 -> $clock2 in all our cases
        self::assertEquals(ClockOrder::HAPPEN_BEFORE, $clock1->compare($clock2));
        self::assertEquals(ClockOrder::HAPPEN_AFTER, $clock2->compare($clock1));
    }

    #[DataProvider('provideCausalityCases')]
    public function testHappenBeforeAndAfter(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // $clock1 -> $clock2 in all our cases
        self::assertTrue($clock1->happenBefore($clock2));
        self::assertFalse($clock1->happenAfter($clock2));

        self::assertTrue($clock2->happenAfter($clock1));
        self::assertFalse($clock2->happenBefore($clock1));
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testHappenBeforeWithUncomparableClocksThrowException(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        $this->expectException(UnComparableException::class);
        $clock1->happenBefore($clock2);
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testHappenAfterWithUncomparableClocksThrowException(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        $this->expectException(UnComparableException::class);
        $clock1->happenAfter($clock2);
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testHappenBeforeWithUncomparableClocksThrowException2(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        $this->expectException(UnComparableException::class);
        $clock2->happenBefore($clock1);
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testHappenAfterWithUncomparableClocksThrowException2(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        $this->expectException(UnComparableException::class);
        $clock2->happenAfter($clock1);
    }

    public static function provideCausalityCases(): \Generator
    {
        yield 'same process and one node: Tep = [0] && Tfp = [1]' => [
          self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(0)]),
          self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1)]),
        ];
        yield 'same process and one node: Tep = [3] && Tfp = [4]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(3)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(4)]),
        ];
        yield 'same process and several nodes: Tep = [1, 0] && Tfp = [2, 0]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1), self::DEFAULT_NODE_2 => new LogicalTimestamp(0)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(2), self::DEFAULT_NODE_2 => new LogicalTimestamp(0)]),
        ];
        yield 'same process and several nodes: Tep = [1, 1] && Tfp = [2, 1]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1), self::DEFAULT_NODE_2 => new LogicalTimestamp(1)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(2), self::DEFAULT_NODE_2 => new LogicalTimestamp(1)]),
        ];
        yield 'same process and several nodes: Tep = [1, 0] && Tfp = [2, 1]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1), self::DEFAULT_NODE_2 => new LogicalTimestamp(0)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(2), self::DEFAULT_NODE_2 => new LogicalTimestamp(1)]),
        ];
        yield 'same process and several nodes: Tep = [1, 7] && Tfp = [2, 10]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1), self::DEFAULT_NODE_2 => new LogicalTimestamp(7)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(2), self::DEFAULT_NODE_2 => new LogicalTimestamp(10)]),
        ];
        yield 'same process and several nodes: Tep = [1, 10] && Tfp = [2, 9] -> This case is not supposed to happen on the same process btw' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1), self::DEFAULT_NODE_2 => new LogicalTimestamp(10)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(2), self::DEFAULT_NODE_2 => new LogicalTimestamp(9)]),
        ];
        yield 'NOT same process and no extra node: Tep = [0, 0] && Tfq = [1, 0]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(0), self::DEFAULT_NODE_2 => new LogicalTimestamp(0)]),
            self::defaultClock2WithContext([self::DEFAULT_NODE => new LogicalTimestamp(0), self::DEFAULT_NODE_2 => new LogicalTimestamp(1)]),
        ];
        yield 'NOT same process and no extra node: Tep = [0, 0] && Tfq = [1, 1]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(0), self::DEFAULT_NODE_2 => new LogicalTimestamp(0)]),
            self::defaultClock2WithContext([self::DEFAULT_NODE => new LogicalTimestamp(1), self::DEFAULT_NODE_2 => new LogicalTimestamp(1)]),
        ];
        yield 'NOT same process and no extra node: Tep = [1, 0] && Tfq = [1, 1]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1), self::DEFAULT_NODE_2 => new LogicalTimestamp(0)]),
            self::defaultClock2WithContext([self::DEFAULT_NODE => new LogicalTimestamp(1), self::DEFAULT_NODE_2 => new LogicalTimestamp(1)]),
        ];
        yield 'NOT same process and no extra node: Tep = [3, 3] && Tfq = [4, 10]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(3), self::DEFAULT_NODE_2 => new LogicalTimestamp(3)]),
            self::defaultClock2WithContext([self::DEFAULT_NODE => new LogicalTimestamp(4), self::DEFAULT_NODE_2 => new LogicalTimestamp(10)]),
        ];
        yield 'NOT same process and no extra node: Tep = [3, 3] && Tfq = [3, 10]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(3), self::DEFAULT_NODE_2 => new LogicalTimestamp(3)]),
            self::defaultClock2WithContext([self::DEFAULT_NODE => new LogicalTimestamp(3), self::DEFAULT_NODE_2 => new LogicalTimestamp(10)]),
        ];

        yield 'NOT same process and one extra node: Tep = [0, 0, 0] && Tfq = [1, 1, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [3, 5, 0] && Tfq = [4, 6, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [3, 5, 1] && Tfq = [4, 6, 3]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(1),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [3, 5, 10] && Tfq = [4, 6, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(10),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(9),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [3, 5, 1] && Tfq = [3, 6, 3]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(1),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [2, 0, 0] && Tfq = [2, 3, 3] ' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(3),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [2, 0, 0] && Tfq = [2, 4, 3] ' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
        ];
    }
}
