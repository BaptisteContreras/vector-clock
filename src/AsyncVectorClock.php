<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\CannotReceiveSameClockInstanceException;
use Dynamophp\VectorClock\Exception\UnComparableException;
use Dynamophp\VectorClock\Exception\UnknownNodeException;

class AsyncVectorClock extends AbstractVectorClock
{
    public function compare(self $clock): ClockOrder
    {
        if (!$this->canBeCompared($clock)) {
            return ClockOrder::NOT_COMPARABLE;
        }

        $otherTimestamps = $clock->getTimestamps();

        if ($this->timestampMap[$this->node]->getValue() < $otherTimestamps[$this->node]->getValue()) {
            return ClockOrder::HAPPEN_BEFORE;
        }

        if ($otherTimestamps[$clock->getNode()]->getValue() < $this->timestampMap[$clock->getNode()]->getValue()) {
            return ClockOrder::HAPPEN_AFTER;
        }

        $hasDifferentTimestamps = false;

        foreach ($this->timestampMap as $currentNode => $currentTimestamps) {
            if (!$currentTimestamps->isEqualTo($otherTimestamps[$currentNode])) {
                $hasDifferentTimestamps = true;
                break;
            }
        }

        return $hasDifferentTimestamps ? ClockOrder::CONCURRENT : ClockOrder::IDENTICAL;
    }

    /**
     * @throws UnComparableException
     */
    public function isIdenticalTo(self $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new UnComparableException();
        }

        return ClockOrder::IDENTICAL === $comparison;
    }

    /**
     * @throws UnComparableException
     */
    public function happenBefore(self $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new UnComparableException();
        }

        return ClockOrder::HAPPEN_BEFORE === $comparison;
    }

    /**
     * @throws UnComparableException
     */
    public function happenAfter(self $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new UnComparableException();
        }

        return ClockOrder::HAPPEN_AFTER === $comparison;
    }

    public function isConcurrentWith(self $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new UnComparableException();
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
        $this->incrementNode();

        return $this;
    }

    public function applySendEvent(): self
    {
        $this->incrementNode();

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

        $this->incrementNode();

        $this->mergeClock($clock);

        return $this;
    }

    /**
     * Two clocks are comparable if they have the same nodes.
     */
    private function canBeCompared(self $clock): bool
    {
        $clockTimestamps = $clock->getTimestamps();

        if (count($this->timestampMap) !== count($clockTimestamps)) {
            return false;
        }

        if (!empty(array_diff_key($this->timestampMap, $clockTimestamps))) {
            return false;
        }

        // This is an invalid state, two clocks on the same node, with equal timestamp for their node, must have the same vector
        if ($this->hasSameNode($clock) && $this->timestampMap[$this->node]->isEqualTo($clockTimestamps[$this->node])) {
            foreach ($this->timestampMap as $currentNode => $currentTimestamp) {
                if (!$clockTimestamps[$currentNode]->isEqualTo($currentTimestamp)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function mergeClock(self $clock): void
    {
        $otherTimestampsMap = $clock->getTimestamps();

        if ($this->timestampMap[$clock->getNode()]->getValue() <= $otherTimestampsMap[$clock->getNode()]->getValue()) {
            $this->setVectorElementValue($clock->getNode(), 1 + $otherTimestampsMap[$clock->getNode()]->getValue());
        }

        foreach ($this->timestampMap as $currentNode => $currentTimestamp) {
            $otherTimestamps = $otherTimestampsMap[$currentNode] ?? null;
            if ($otherTimestamps && $otherTimestamps->getValue() > $currentTimestamp->getValue()) {
                $this->setVectorElementValue($currentNode, $otherTimestamps->getValue());
            }
        }
    }
}
