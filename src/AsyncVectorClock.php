<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\UnComparableException;

class AsyncVectorClock extends AbstractVectorClock
{
    public function compare(AsyncVectorClock $clock): ClockOrder
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
    public function isIdenticalTo(AsyncVectorClock $clock): bool
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
    public function happenBefore(AsyncVectorClock $clock): bool
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
    public function happenAfter(AsyncVectorClock $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new UnComparableException();
        }

        return ClockOrder::HAPPEN_AFTER === $comparison;
    }

    public function isConcurrentWith(AsyncVectorClock $clock): bool
    {
        $comparison = $this->compare($clock);

        if (ClockOrder::NOT_COMPARABLE === $comparison) {
            throw new UnComparableException();
        }

        return ClockOrder::CONCURRENT === $comparison;
    }

    public function canBeComparedWith(AsyncVectorClock $clock): bool
    {
        return $this->canBeCompared($clock);
    }

    public function hasSameNode(AsyncVectorClock $clock): bool
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

    public function applyReceiveEvent(AsyncVectorClock $clock): self
    {
        // TODO throw exception if $clock->getNode() is unknown
        // TODO throw exception if $clock === $this
        $this->incrementNode();

        $this->mergeClock($clock);

        return $this;
    }

    /**
     * Two clocks are comparable if they have the same nodes.
     */
    private function canBeCompared(AsyncVectorClock $clock): bool
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

    private function mergeClock(AsyncVectorClock $clock): void
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
