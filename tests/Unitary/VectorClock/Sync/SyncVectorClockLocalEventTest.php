<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync;

use Dynamophp\VectorClock\Exception\ClockIsNotIdleException;
use Dynamophp\VectorClock\LogicalTimestamp;
use Dynamophp\VectorClock\SyncClockState;
use Dynamophp\VectorClock\SyncVectorClock;

class SyncVectorClockLocalEventTest extends AbstractSyncVectorTest
{
    public function testApplyLocalEvent(): void
    {
        $clock = new SyncVectorClock(self::DEFAULT_NODE);

        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));

        $clock->applyLocalEvent();

        self::assertEquals(1, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));

        $clock->applyLocalEvent();
        $clock->applyLocalEvent();
        $clock->applyLocalEvent();

        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));

        $clock->addNode(self::DEFAULT_NODE_2);

        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->applyLocalEvent();

        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->removeNode(self::DEFAULT_NODE_2);

        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));

        $clock->applyLocalEvent();

        self::assertEquals(6, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
    }

    public function testApplyLocalEventWithInitialContext(): void
    {
        $clock = new SyncVectorClock(self::DEFAULT_NODE, [self::DEFAULT_NODE_2 => LogicalTimestamp::init()]);

        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->applyLocalEvent();

        self::assertEquals(1, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->applyLocalEvent();
        $clock->applyLocalEvent();
        $clock->applyLocalEvent();

        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->addNode(self::DEFAULT_NODE_3);

        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_3));

        $clock->applyLocalEvent();

        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_3));

        $clock->removeNode(self::DEFAULT_NODE_3);

        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->applyLocalEvent();

        self::assertEquals(6, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
    }

    public function testCannotAppLocalEventIfStateIsNotIdle(): void
    {
        $clock = new SyncVectorClock(self::DEFAULT_NODE, [self::DEFAULT_NODE_2 => LogicalTimestamp::init()]);
        self::assertEquals(SyncClockState::IDLE, $clock->getCommunicationState());

        $clock->applySendEvent(self::DEFAULT_NODE_2);
        self::assertEquals(SyncClockState::COMMUNICATING, $clock->getCommunicationState());

        self::expectException(ClockIsNotIdleException::class);
        $clock->applyLocalEvent();
    }
}
