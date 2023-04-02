<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\AsyncVectorClock;
use Dynamophp\VectorClock\LogicalTimestamp;

class AsyncVectorClockSendEventTest extends AbstractAsyncVectorTest
{
    public function testApplySendEvent(): void
    {
        $clock = new AsyncVectorClock(self::DEFAULT_NODE);

        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));

        $clock->applySendEvent();

        self::assertEquals(1, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));

        $clock->applySendEvent();
        $clock->applySendEvent();
        $clock->applySendEvent();

        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));

        $clock->addNode(self::DEFAULT_NODE_2);

        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->applySendEvent();

        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->removeNode(self::DEFAULT_NODE_2);

        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));

        $clock->applySendEvent();

        self::assertEquals(6, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
    }

    public function testApplySendEventWithInitialContext(): void
    {
        $clock = new AsyncVectorClock(self::DEFAULT_NODE, [self::DEFAULT_NODE_2 => LogicalTimestamp::init()]);

        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->applySendEvent();

        self::assertEquals(1, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->applySendEvent();
        $clock->applySendEvent();
        $clock->applySendEvent();

        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->addNode(self::DEFAULT_NODE_3);

        self::assertEquals(4, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_3));

        $clock->applySendEvent();

        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_3));

        $clock->removeNode(self::DEFAULT_NODE_3);

        self::assertEquals(5, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));

        $clock->applySendEvent();

        self::assertEquals(6, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE));
        self::assertEquals(0, $this->getTimestampValueForNode($clock, self::DEFAULT_NODE_2));
    }
}
