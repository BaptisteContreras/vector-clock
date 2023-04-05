<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync;

use Dynamophp\VectorClock\LogicalTimestamp;
use Dynamophp\VectorClock\SyncClockState;
use Dynamophp\VectorClock\SyncVectorClock;
use PHPUnit\Framework\TestCase;

abstract class AbstractSyncVectorTest extends TestCase
{
    protected const DEFAULT_NODE = 'NODE-TEST';
    protected const DEFAULT_NODE_2 = 'NODE-TEST-2';
    protected const DEFAULT_NODE_3 = 'NODE-TEST-3';

    protected function getTimestampValueForNode(SyncVectorClock $clock, string $node): int
    {
        return $clock->getTimestamps()[$node]->getValue();
    }

    protected function assertIsIdle(SyncVectorClock $clock): void
    {
        self::assertEquals(SyncClockState::IDLE, $clock->getCommunicationState());
        self::assertNull($clock->getCommunicatingNode());
        self::assertTrue($clock->isIdle());
        self::assertFalse($clock->isCommunicating());
    }

    protected function assertIsCommunicating(SyncVectorClock $clock, string $nodeCommunicatingWith): void
    {
        self::assertEquals(SyncClockState::COMMUNICATING, $clock->getCommunicationState());
        self::assertEquals($nodeCommunicatingWith, $clock->getCommunicatingNode());
        self::assertTrue($clock->isCommunicating());
        self::assertFalse($clock->isIdle());
    }

    public static function defaultClockWithContext(array $initialContext = []): SyncVectorClock
    {
        return new SyncVectorClock(self::DEFAULT_NODE, $initialContext);
    }

    public static function defaultClock2WithContext(array $initialContext = []): SyncVectorClock
    {
        return new SyncVectorClock(self::DEFAULT_NODE_2, $initialContext);
    }

    public static function defaultClock3WithContext(array $initialContext = []): SyncVectorClock
    {
        return new SyncVectorClock(self::DEFAULT_NODE_3, $initialContext);
    }

    public static function getExpectedResult(int $node1, ?int $node2 = null, ?int $node3 = null): array
    {
        $expected = [self::DEFAULT_NODE => $node1];

        if (null !== $node2) {
            $expected[self::DEFAULT_NODE_2] = $node2;
        }

        if (null !== $node3) {
            $expected[self::DEFAULT_NODE_3] = $node3;
        }

        return $expected;
    }

    public static function getInitContextWithNodes(array $nodes): SyncVectorClock
    {
        return array_reduce($nodes, function (array $carry, string $node) {
            $carry[$node] = LogicalTimestamp::init();

            return $carry;
        }, []);
    }

    public static function provideNotComparableClocks(): \Generator
    {
        yield 'Not same size' => [
            self::defaultClockWithContext(),
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init()]),
        ];
        yield 'Not same size inversed' => [
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init()]),
            self::defaultClockWithContext(),
        ];
        yield 'Not same size with several elements in each clocks' => [
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init()]),
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init(), 'NODE-3' => LogicalTimestamp::init()]),
        ];
        yield 'Not same size with several elements in each clocks inversed' => [
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init(), 'NODE-3' => LogicalTimestamp::init()]),
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init()]),
        ];
        yield 'Have different main node' => [
            self::defaultClockWithContext(),
            self::defaultClock2WithContext(),
        ];
        yield 'Have different main node and several elements' => [
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init(), 'NODE-4' => LogicalTimestamp::init()]),
            self::defaultClock2WithContext(['NODE-2' => LogicalTimestamp::init(), 'NODE-4' => LogicalTimestamp::init()]),
        ];
        yield 'All elements are different' => [
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init(), 'NODE-4' => LogicalTimestamp::init()]),
            self::defaultClock2WithContext(['NODE-6' => LogicalTimestamp::init(), 'NODE-7' => LogicalTimestamp::init()]),
        ];
        yield 'Have different node' => [
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init()]),
            self::defaultClockWithContext(['NODE-3' => LogicalTimestamp::init()]),
        ];
        yield 'Have different node with more elements' => [
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init(), 'NODE-4' => LogicalTimestamp::init()]),
            self::defaultClockWithContext(['NODE-2' => LogicalTimestamp::init(), 'NODE-3' => LogicalTimestamp::init()]),
        ];
        yield 'Have different node and different main node' => [
            self::defaultClockWithContext(['NODE-9' => LogicalTimestamp::init()]),
            self::defaultClock2WithContext(['NODE-3' => LogicalTimestamp::init()]),
        ];
        yield 'Have different node with more elements and different main node' => [
            self::defaultClockWithContext(['NODE-9' => LogicalTimestamp::init(), 'NODE-4' => LogicalTimestamp::init()]),
            self::defaultClock2WithContext(['NODE-10' => LogicalTimestamp::init(), 'NODE-3' => LogicalTimestamp::init()]),
        ];
        yield 'same process and one extra node: Tep = [3, 3] && Tfq = [3, 10] -> is this an invalid state that cannot be compared' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(3), self::DEFAULT_NODE_2 => new LogicalTimestamp(3)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(3), self::DEFAULT_NODE_2 => new LogicalTimestamp(10)]),
        ];
        yield 'same process and two extra node: Tep = [3, 4, 4] && Tfq = [3, 4, 3] ' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(4),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(3),
            ]),
        ];
    }
}
