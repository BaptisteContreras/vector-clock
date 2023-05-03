<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\CannotReceiveSameClockInstanceException;
use Dynamophp\VectorClock\Exception\IncomparableException;
use Dynamophp\VectorClock\Exception\UnknownNodeException;

class AsyncVectorClock extends AbstractVectorClock
{
    public function compare(self $clock): ClockOrder
    {
        if (!$this->canBeCompared($clock)) {
            return ClockOrder::NOT_COMPARABLE;
        }

        $otherClockTimestamps = $clock->getTimestamps();

        if ($this->timestamps[$this->node]->getValue() < $otherClockTimestamps[$this->node]->getValue()) {
            return ClockOrder::HAPPEN_BEFORE;
        }

        if ($otherClockTimestamps[$clock->getNode()]->getValue() < $this->timestamps[$clock->getNode()]->getValue()) {
            return ClockOrder::HAPPEN_AFTER;
        }

        $hasDifferentTimestamp = false;

        foreach ($this->timestamps as $currentNode => $currentTimestamp) {
            if (!$currentTimestamp->isEqualTo($otherClockTimestamps[$currentNode])) {
                $hasDifferentTimestamp = true;
                break;
            }
        }

        return $hasDifferentTimestamp ? ClockOrder::CONCURRENT : ClockOrder::IDENTICAL;
    }

    /**
     * @throws IncomparableException
     */
    public function isIdenticalTo(self $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new IncomparableException();
        }

        return ClockOrder::IDENTICAL === $comparison;
    }

    /**
     * @throws IncomparableException
     */
    public function happenBefore(self $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new IncomparableException();
        }

        return ClockOrder::HAPPEN_BEFORE === $comparison;
    }

    /**
     * @throws IncomparableException
     */
    public function happenAfter(self $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new IncomparableException();
        }

        return ClockOrder::HAPPEN_AFTER === $comparison;
    }

    /**
     * @throws IncomparableException
     */
    public function isConcurrentWith(self $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new IncomparableException();
        }

        return ClockOrder::CONCURRENT === $comparison;
    }

    public function canBeComparedWith(self $clock): bool
    {
        return $this->canBeCompared($clock);
    }

    public function hasSameNode(self $clock): bool
    {
        return $this->node === $clock->getNode();
    }

    public function applyLocalEvent(): self
    {
        $this->tick();

        return $this;
    }

    public function applySendEvent(): self
    {
        $this->tick();

        return $this;
    }

    /**
     * @throws CannotReceiveSameClockInstanceException
     * @throws UnknownNodeException
     */
    public function applyReceiveEvent(self $clock): self
    {
        if ($this === $clock) {
            throw new CannotReceiveSameClockInstanceException();
        }

        $this->ensureNodeIsInVector($clock->getNode());

        $this->tick();

        $this->mergeClock($clock);

        return $this;
    }

    /**
     * Two clocks are comparable if they have the same nodes.
     */
    private function canBeCompared(self $clock): bool
    {
        $otherClockTimestamps = $clock->getTimestamps();

        if (count($this->timestamps) !== count($otherClockTimestamps)) {
            return false;
        }

        if (!empty(array_diff_key($this->timestamps, $otherClockTimestamps))) {
            return false;
        }

        // This is an invalid state, two clocks on the same node, with equal timestamp for their node, must have the same vector
        if ($this->hasSameNode($clock) && $this->timestamps[$this->node]->isEqualTo($otherClockTimestamps[$this->node])) {
            foreach ($this->timestamps as $currentNode => $currentTimestamp) {
                if (!$otherClockTimestamps[$currentNode]->isEqualTo($currentTimestamp)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function mergeClock(self $clock): void
    {
        $otherClockTimestamps = $clock->getTimestamps();

        if ($this->timestamps[$clock->getNode()]->getValue() <= $otherClockTimestamps[$clock->getNode()]->getValue()) {
            $this->setNodeValue($clock->getNode(), 1 + $otherClockTimestamps[$clock->getNode()]->getValue());
        }

        foreach ($this->timestamps as $currentNode => $currentTimestamp) {
            $otherClockCurrentTimestamp = $otherClockTimestamps[$currentNode] ?? null;

            if ($otherClockCurrentTimestamp && $otherClockCurrentTimestamp->getValue() > $currentTimestamp->getValue()) {
                $this->setNodeValue($currentNode, $otherClockCurrentTimestamp->getValue());
            }
        }
    }
}
