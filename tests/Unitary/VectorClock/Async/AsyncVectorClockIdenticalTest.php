<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\AsyncVectorClock;
use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\Exception\IncomparableException;
use Dynamophp\VectorClock\LogicalTimestamp;
use PHPUnit\Framework\Attributes\DataProvider;

use function PHPUnit\Framework\assertTrue;

class AsyncVectorClockIdenticalTest extends AbstractAsyncVectorTest
{
    #[DataProvider('provideIdenticalCases')]
    public function testCompareIdentical(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        // $clock1 == $clock2 in all our cases
        self::assertEquals(ClockOrder::IDENTICAL, $clock1->compare($clock2));
        self::assertEquals(ClockOrder::IDENTICAL, $clock2->compare($clock1));
    }

    #[DataProvider('provideIdenticalCases')]
    public function testIsIdentical(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        // $clock1 == $clock2 in all our cases
        assertTrue($clock1->isIdenticalTo($clock2));
        assertTrue($clock2->isIdenticalTo($clock1));
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testIsIdenticalWithUncomparableClocksThrowException(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        self::expectException(IncomparableException::class);
        $clock1->isIdenticalTo($clock2);
    }

    #[DataProvider('provideNotComparableClocks')]
    public function testIsIdenticalWithUncomparableClocksThrowException2(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        self::expectException(IncomparableException::class);
        $clock2->isIdenticalTo($clock1);
    }

    public static function provideIdenticalCases(): \Generator
    {
        yield 'same process and one node: Tep = [0] && Tfp = [0]' => [
          self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(0)]),
          self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(0)]),
        ];
        yield 'same process and one node: Tep = [1] && Tfp = [1]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1)]),
        ];
        yield 'same process and one node: Tep = [2] && Tfp = [2]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(2)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(2)]),
        ];
        yield 'same process and several nodes: Tep = [0, 0] && Tfp = [0, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
        ];
        yield 'same process and several nodes: Tep = [3, 4] && Tfp = [3, 4]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
            ]),
        ];
        yield 'two different process: Tep = [0, 0] && Tfp = [0, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
        ];
        yield 'two different process: Tep = [3, 1] && Tfp = [3, 1]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
            ]),
        ];
        yield 'two different process and one extra node: Tep = [3, 1, 99] && Tfp = [3, 1, 99]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(99),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(1),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(99),
            ]),
        ];
        yield 'two different process and one extra node: Tep = [10, 10, 10] && Tfp = [10, 10, 10]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(10),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(10),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(10),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(10),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(10),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(10),
            ]),
        ];
    }
}
