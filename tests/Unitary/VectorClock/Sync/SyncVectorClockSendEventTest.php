<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync;

use Dynamophp\VectorClock\Exception\ClockIsNotIdleException;
use Dynamophp\VectorClock\Exception\UnknownNodeException;
use Dynamophp\VectorClock\LogicalTimestamp;
use Dynamophp\VectorClock\SyncVectorClock;
use PHPUnit\Framework\Attributes\DataProvider;

class SyncVectorClockSendEventTest extends AbstractSyncVectorTest
{
    public function testApplySendEvent(): void
    {
        $clock = self::defaultClockWithContext();
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE);
        self::assertEquals(1, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE);

        $clock->applyReceiveEvent(self::defaultClockWithContext($clock->getTimestamps()));
        self::assertEquals(1, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE);
        self::assertEquals(2, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE);

        $clock->applyReceiveEvent(self::defaultClockWithContext($clock->getTimestamps()));
        self::assertEquals(2, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertIsIdle($clock);

        $clock->addNode(self::DEFAULT_NODE_2);
        self::assertEquals(2, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE);
        self::assertEquals(3, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE);

        $clock->applyReceiveEvent(self::defaultClockWithContext($clock->getTimestamps()));
        self::assertEquals(3, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE_2);
        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE_2);

        $clock->applyReceiveEvent(self::defaultClock2WithContext($clock->getTimestamps()));
        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsIdle($clock);

        $clock->removeNode(self::DEFAULT_NODE_2);
        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE);
        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE);
    }

    public function testApplySendEventWithInitialContext(): void
    {
        $clock = self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]);
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE);
        self::assertEquals(1, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE);

        $clock->applyReceiveEvent(self::defaultClockWithContext($clock->getTimestamps()));
        self::assertEquals(1, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE_2);
        self::assertEquals(2, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE_2);

        $clock->applyReceiveEvent(self::defaultClock2WithContext($clock->getTimestamps()));
        self::assertEquals(2, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsIdle($clock);

        $clock->addNode(self::DEFAULT_NODE_3);
        self::assertEquals(2, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_3));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE);
        self::assertEquals(3, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_3));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE);

        $clock->applyReceiveEvent(self::defaultClockWithContext($clock->getTimestamps()));
        self::assertEquals(3, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE_3);
        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_3));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE_3);

        $clock->applyReceiveEvent(self::defaultClock3WithContext($clock->getTimestamps()));
        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_3));
        self::assertIsIdle($clock);

        $clock->removeNode(self::DEFAULT_NODE_3);
        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsIdle($clock);

        $clock->applySendEvent(self::DEFAULT_NODE_2);
        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertIsCommunicating($clock, self::DEFAULT_NODE_2);
    }

    #[DataProvider('provideUnknownNode')]
    public function testApplySendEventOnUnknownNodeFails(SyncVectorClock $clock, string $unknownNode): void
    {
        self::expectException(UnknownNodeException::class);

        $clock->applySendEvent($unknownNode);
    }

    #[DataProvider('provideNotIdleCases')]
    public function testCannotApplySendEventIfStateIsNotIdle(SyncVectorClock $clock, string $node): void
    {
        self::expectException(ClockIsNotIdleException::class);

        $clock->applySendEvent($clock->getNode()); // Start a communication with itself
        self::assertIsCommunicating($clock, $clock->getNode());

        $clock->applySendEvent($node); // Start a new communication while not being idle...
    }

    public static function provideUnknownNode(): \Generator
    {
        yield [
            self::defaultClockWithContext(),
            self::DEFAULT_NODE_2,
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_3,
        ];
    }

    public static function provideNotIdleCases(): \Generator
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
        yield [
            self::defaultClockWithContext(),
            self::DEFAULT_NODE_2,
        ];
        yield [
            self::defaultClockWithContext([self::DEFAULT_NODE_2 => LogicalTimestamp::init()]),
            self::DEFAULT_NODE_3,
        ];
    }
}
