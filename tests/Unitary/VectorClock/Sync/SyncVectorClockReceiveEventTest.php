<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync;

use Dynamophp\VectorClock\Exception\CannotReceiveSameClockInstanceException;
use Dynamophp\VectorClock\Exception\UnexpectedReceiveEventException;
use Dynamophp\VectorClock\Exception\UnknownNodeException;
use Dynamophp\VectorClock\LogicalTimestamp;
use Dynamophp\VectorClock\SyncVectorClock;
use PHPUnit\Framework\Attributes\DataProvider;

class SyncVectorClockReceiveEventTest extends AbstractSyncVectorTest
{
    #[DataProvider('provideReceiveData')]
    public function testApplyReceiveEvent(SyncVectorClock $clock1, SyncVectorClock $clock2, array $expectedResult): void
    {
        $beforeClock2Timestamps = $clock2->getTimestamps();

        self::assertIsIdle($clock1);
        self::assertIsIdle($clock2);

        $clock1->applyReceiveEvent($clock2);

        self::assertIsIdle($clock1);
        self::assertIsIdle($clock2);

        $timestamps = $clock1->getTimestamps();
        $timestampsClock2 = $clock2->getTimestamps();

        self::assertCount(count($expectedResult), $timestamps);
        self::assertCount(count($beforeClock2Timestamps), $timestampsClock2);

        foreach ($expectedResult as $expectedNode => $expectedValue) {
            self::assertEquals($expectedValue, $timestamps[$expectedNode]->getValue());
        }

        foreach ($beforeClock2Timestamps as $beforeNode => $beforeValue) {
            self::assertEquals($beforeValue, $timestampsClock2[$beforeNode]);
        }
    }

    #[DataProvider('provideReceiveData')]
    public function testApplyReceiveEventAfterSend(SyncVectorClock $clock1, SyncVectorClock $clock2, array $expectedResult): void
    {
        $beforeClock2Timestamps = $clock2->getTimestamps();

        self::assertIsIdle($clock1);
        self::assertIsIdle($clock2);
        $clock1->applySendEvent($clock2->getNode());

        self::assertIsCommunicating($clock1, $clock2->getNode());
        self::assertIsIdle($clock2);

        $clock1->applyReceiveEvent($clock2);

        self::assertIsIdle($clock1);
        self::assertIsIdle($clock2);

        $timestamps = $clock1->getTimestamps();
        $timestampsClock2 = $clock2->getTimestamps();

        self::assertCount(count($expectedResult), $timestamps);
        self::assertCount(count($beforeClock2Timestamps), $timestampsClock2);

        foreach ($expectedResult as $expectedNode => $expectedValue) {
            // We don't have to add + 1 to $expectedValue because if a clock in communication
            // receive another one, we don't increment the local timestamp
            self::assertEquals($expectedValue, $timestamps[$expectedNode]->getValue());
        }

        foreach ($beforeClock2Timestamps as $beforeNode => $beforeValue) {
            self::assertEquals($beforeValue, $timestampsClock2[$beforeNode]);
        }
    }

    #[DataProvider('provideUnexpectedReceiveData')]
    public function testApplyReceiveEventFailIfNodesMismatch(SyncVectorClock $clock1, string $expectedNode, SyncVectorClock $clock2): void
    {
        self::expectException(UnexpectedReceiveEventException::class);

        self::assertIsIdle($clock1);
        self::assertIsIdle($clock2);
        $clock1->applySendEvent($expectedNode);

        self::assertIsCommunicating($clock1, $expectedNode);
        self::assertIsIdle($clock2);

        $clock1->applyReceiveEvent($clock2);
    }

    #[DataProvider('provideUnknownData')]
    public function testApplyReceiveEventFailIfNodeIsUnknown(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        self::expectException(UnknownNodeException::class);

        self::assertIsIdle($clock1);
        self::assertIsIdle($clock2);

        $clock1->applyReceiveEvent($clock2);
    }

    #[DataProvider('provideSameInstanceData')]
    public function testApplyReceiveEventWithSameInstanceWhileIdleFails(SyncVectorClock $clock): void
    {
        self::expectException(CannotReceiveSameClockInstanceException::class);

        self::assertIsIdle($clock);

        $clock->applyReceiveEvent($clock);
    }

    #[DataProvider('provideSameInstanceAndWrongNodeData')]
    public function testApplyReceiveEventWithSameInstanceWhileInCommunicationFails(SyncVectorClock $clock, string $expectedCommunicatingNode): void
    {
        self::expectException(CannotReceiveSameClockInstanceException::class);

        self::assertIsIdle($clock);
        $clock->applySendEvent($expectedCommunicatingNode);
        self::assertIsCommunicating($clock, $expectedCommunicatingNode);

        $clock->applyReceiveEvent($clock);
    }

    public static function provideReceiveData(): \Generator
    {
        yield 'from same process: [0] <- [0] : [1]' => [
            self::defaultClockWithContext(),
            self::defaultClockWithContext(),
            self::getExpectedResult(1),
        ];
        yield 'from same process: [0] <- [2] : [3]' => [
            self::defaultClockWithContext(),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(2)]),
            self::getExpectedResult(2),
        ];
        yield 'from same process: [1] <- [3] : [3]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(1)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(3)]),
            self::getExpectedResult(3),
        ];
        yield 'from same process: [4] <- [0] : [5]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(4)]),
            self::defaultClockWithContext(),
            self::getExpectedResult(5),
        ];
        yield 'from same process: [4] <- [5] : [5]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(4)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(5)]),
            self::getExpectedResult(5),
        ];
        yield 'from same process: [4] <- [3] : [5]' => [
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(4)]),
            self::defaultClockWithContext([self::DEFAULT_NODE => new LogicalTimestamp(3)]),
            self::getExpectedResult(5),
        ];
        yield 'from same process: [0, 0] <- [0, 0] : [1, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(1, 0),
        ];
        yield 'from same process: [0, 0] <- [2, 0] : [2, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(2, 0),
        ];
        yield 'from same process: [0, 0] <- [3, 5] : [3, 5]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
            ]),
            self::getExpectedResult(3, 5),
        ];
        yield 'from same process: [0, 10] <- [3, 5] : [3, 10]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(10),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
            ]),
            self::getExpectedResult(3, 10),
        ];
        yield 'from same process: [4, 7] <- [5, 3] : [5, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(3),
            ]),
            self::getExpectedResult(5, 7),
        ];
        yield 'from same process: [4, 3] <- [5, 7] : [5, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(3),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(5, 7),
        ];
        yield 'from same process: [4, 7] <- [5, 7] : [5, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(5, 7),
        ];
        yield 'from same process: [4, 0] <- [3, 0] : [5, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(5, 0),
        ];
        yield 'from same process: [4, 8] <- [3, 9] : [5, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(8),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::getExpectedResult(5, 9),
        ];
        yield 'from same process: [4, 9] <- [3, 8] : [5, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(8),
            ]),
            self::getExpectedResult(5, 9),
        ];
        yield 'from same process: [4, 9] <- [3, 9] : [5, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::getExpectedResult(5, 9),
        ];

        yield 'from same process and one extra ignored: [0, 0] <- [0] : [1, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(1, 0),
        ];
        yield 'from same process and one extra ignored: [0] <- [0, 0] : [1]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(1),
        ];
        yield 'from same process and one extra ignored: [4, 7] <- [5] : [5, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
            ]),
            self::getExpectedResult(5, 7),
        ];
        yield 'from same process and one extra ignored: [4] <- [5, 7] : [5]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(5),
        ];

        yield 'from same process and one extra ignored: [4, 7] <- [3] : [5, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
            ]),
            self::getExpectedResult(5, 7),
        ];

        yield 'from same process and one extra ignored: [4] <- [3, 7] : [5]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
            ]),
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(5),
        ];

        yield 'from different process: [0, 0] <- [0, 0] : [1, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(1, 0),
        ];
        yield 'from different process: [0, 0] <- [2, 0] : [2, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(2),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(2, 0),
        ];
        yield 'from different process: [0, 0] <- [3, 5] : [3, 5]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
            ]),
            self::getExpectedResult(3, 5),
        ];
        yield 'from different process: [0, 10] <- [3, 5] : [3, 10]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(10),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
            ]),
            self::getExpectedResult(3, 10),
        ];
        yield 'from different process: [4, 7] <- [5, 7] : [5, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(5, 7),
        ];
        yield 'from different process: [4, 3] <- [5, 7] : [5, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(3),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(5),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(5, 7),
        ];
        yield 'from different process: [4, 7] <- [4, 7] : [5, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(5, 7),
        ];
        yield 'from different process: [7, 4] <- [4, 3] : [8, 4]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(7),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(4),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(3),
            ]),
            self::getExpectedResult(8, 4),
        ];
        yield 'from different process: [7, 8] <- [3, 3] : [8, 8]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(7),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(8),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(3),
            ]),
            self::getExpectedResult(8, 8),
        ];
        yield 'from different process: [4, 8] <- [3, 9] : [5, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(8),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::getExpectedResult(5, 9),
        ];
        yield 'from different process: [4, 9] <- [3, 8] : [5, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(8),
            ]),
            self::getExpectedResult(5, 9),
        ];
        yield 'from different process: [4, 9] <- [3, 9] : [5, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::getExpectedResult(5, 9),
        ];
        yield 'from different process: [3, 9] <- [4, 9] : [4, 9]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
            ]),
            self::getExpectedResult(4, 9),
        ];
        yield 'from different process: [3, 9, 15] <- [4, 9, 14] : [4, 9, 15]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(14),
            ]),
            self::getExpectedResult(4, 9, 15),
        ];
        yield 'from different process: [3, 9, 14] <- [4, 9, 15] : [4, 9, 15]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(14),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::getExpectedResult(4, 9, 15),
        ];
        yield 'from different process: [3, 9, 15] <- [4, 9, 15] : [4, 9, 15]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(3),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(9),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::getExpectedResult(4, 9, 15),
        ];

        yield 'from different process and one extra ignored: [0, 0, 0] <- [0, 0] : [1, 0, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(1, 0, 0),
        ];
        yield 'from different process and one extra ignored: [0, 0] <- [0, 0, 0] : [1, 0, 0]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(0),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(0),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(0),
            ]),
            self::getExpectedResult(1, 0),
        ];
        yield 'from different process and one extra ignored: [4, 5, 15] <- [6, 7] : [6, 7, 15]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(6),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(6, 7, 15),
        ];
        yield 'from different process and one extra ignored: [4, 5] <- [6, 7, 15] : [6, 7]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(4),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(5),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(6),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::getExpectedResult(6, 7),
        ];
        yield 'from different process and one extra ignored: [10, 11, 15] <- [6, 7] : [11, 11, 15]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(10),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(11),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(6),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
            ]),
            self::getExpectedResult(11, 11, 15),
        ];
        yield 'from different process and one extra ignored: [10, 11] <- [6, 7, 15] : [11, 11]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(10),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(11),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(6),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(7),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::getExpectedResult(11, 11),
        ];

        yield 'from different process and one extra ignored: [6, 6, 15] <- [6, 6] : [7, 6, 15]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(6),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(6),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
            ]),
            self::getExpectedResult(7, 6, 15),
        ];
        yield 'from different process and one extra ignored: [6, 6] <- [6, 6, 15] : [7, 6]' => [
            self::defaultClockWithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(6),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
            ]),
            self::defaultClock2WithContext([
                self::DEFAULT_NODE => new LogicalTimestamp(6),
                self::DEFAULT_NODE_2 => new LogicalTimestamp(6),
                self::DEFAULT_NODE_3 => new LogicalTimestamp(15),
            ]),
            self::getExpectedResult(7, 6),
        ];
    }

    public static function provideUnexpectedReceiveData(): \Generator
    {
        yield [
            self::defaultClockWithContext(),
            self::DEFAULT_NODE,
            self::defaultClock2WithContext(),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE,
            self::defaultClock2WithContext(),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_2,
            self::defaultClockWithContext(),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_2,
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_2,
            self::defaultClock3WithContext(),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_2,
            self::defaultClock3WithContext([self::DEFAULT_NODE => LogicalTimestamp::init()]),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_2,
            self::defaultClock3WithContext([self::DEFAULT_NODE => LogicalTimestamp::init(), self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_2,
            self::defaultClock3WithContext([self::DEFAULT_NODE => LogicalTimestamp::init(), self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init(), self::DEFAULT_NODE_3 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_2,
            self::defaultClock3WithContext([self::DEFAULT_NODE => LogicalTimestamp::init(), self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE,
            self::defaultClock3WithContext([self::DEFAULT_NODE => LogicalTimestamp::init(), self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
        ];
    }

    public static function provideUnknownData(): \Generator
    {
        yield [
            self::defaultClockWithContext(),
            self::defaultClock2WithContext(),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::defaultClock3WithContext(),
        ];
    }

    public static function provideSameInstanceData(): \Generator
    {
        yield [
            self::defaultClockWithContext(),
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
        ];
    }

    public static function provideSameInstanceAndWrongNodeData(): \Generator
    {
        yield [
            self::defaultClockWithContext(),
            self::DEFAULT_NODE,
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE,
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_2,
        ];
    }
}
