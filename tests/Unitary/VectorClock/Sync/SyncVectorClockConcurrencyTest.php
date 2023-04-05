<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\Exception\UnComparableException;
use Dynamophp\VectorClock\LogicalTimestamp;
use Dynamophp\VectorClock\SyncVectorClock;
use Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync\AbstractSyncVectorTest;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertTrue;

class SyncVectorClockConcurrencyTest extends AbstractSyncVectorTest
{
    #[DataProvider('provideConcurrencyCases')]
    public function testCompareConcurrency(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // $clock1 <-> $clock2 in all our cases
        self::assertEquals(ClockOrder::CONCURRENT, $clock1->compare($clock2));
        self::assertEquals(ClockOrder::CONCURRENT, $clock2->compare($clock1));
    }

    #[DataProvider('provideConcurrencyCases')]
    public function testIsConcurrentWith(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // $clock1 <-> $clock2 in all our cases
        assertTrue($clock1->isConcurrentWith($clock2));
        assertTrue($clock2->isConcurrentWith($clock1));
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testIsConcurrentWithUncomparableClocksThrowException(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        $this->expectException(UnComparableException::class);
        $clock1->isConcurrentWith($clock2);
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testIsConcurrentWithUncomparableClocksThrowException2(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        $this->expectException(UnComparableException::class);
        $clock2->isConcurrentWith($clock1);
    }

    public static function provideConcurrencyCases(): \Generator
    {
        // Tep[p] > Tfq[p] || Tep[q] >= Tfq[q]
        //  &&
        // Tfq[q] > Tep[q] || Tfq[p] >= Tep[p]
        // -->
        // Tep[p] > Tfq[p] && Tfq[q] > Tep[q]
        // ||
        // Tep[q] >= Tfq[q] && Tfq[p] >= Tep[p]

        yield 'NOT same process and no extra node: Tep = [1, 0] && Tfq = [0, 1]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0)]
            ),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
            ]),
        ];
        yield 'NOT same process and no extra node: Tep = [9, 9] && Tfq = [8, 10]' => [
            self::defaultClockWithContext([
                    self::DEFAULT_NODE => new LogicalTimestamp(9),
                    self::DEFAULT_NODE_2 => new LogicalTimestamp(9)]
            ),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(8),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(10),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [1, 0, 0] && Tfq = [0, 1, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [1, 0, 9] && Tfq = [0, 1, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(9),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [1, 0, 0] && Tfq = [0, 1, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(9),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [1, 1, 0] && Tfq = [1, 1, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(9),
            ]),
        ];
        yield 'NOT same process and one extra node: Tep = [1, 1, 9] && Tfq = [1, 1, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(9),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(1),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [5, 3, 8] && Tfq = [4, 10, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(3),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(8),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(10),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(7),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [5, 3, 7] && Tfq = [4, 10, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(3),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(7),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(10),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(7),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [2, 4, 3]  && Tfq = [2, 4, 4] ' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(4),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [3, 4, 3]  && Tfq = [5, 4, 3] ' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
        ];

        yield 'NOT same process and one extra node: Tep = [4, 6, 3]  && Tfq = [4, 5, 3] ' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
        ];
        yield 'NOT same process and one extra node: Tep = [2, 4, 3]  && Tfq = [3, 4, 3] ' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
        ];
    }
}
