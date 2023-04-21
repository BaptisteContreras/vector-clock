<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\CannotReceiveSameClockInstanceException;
use Dynamophp\VectorClock\Exception\ClockIsNotIdleException;
use Dynamophp\VectorClock\Exception\UnComparableException;
use Dynamophp\VectorClock\Exception\UnexpectedReceiveEventException;
use Dynamophp\VectorClock\Exception\UnknownNodeException;

class SyncVectorClock extends AbstractVectorClock
{
    private ?string $communicatingNode = null;

    public function compare(self $clock): ClockOrder
    {
        if (!$this->canBeCompared($clock)) {
            return ClockOrder::NOT_COMPARABLE;
        }

        $otherTimestamps = $clock->getTimestamps();

        if ($this->timestampMap[$this->node]->getValue() <= $otherTimestamps[$this->node]->getValue()
            && $this->timestampMap[$clock->getNode()]->getValue() < $otherTimestamps[$clock->getNode()]->getValue()
        ) {
            return ClockOrder::HAPPEN_BEFORE;
        }

        if ($otherTimestamps[$clock->getNode()]->getValue() <= $this->timestampMap[$clock->getNode()]->getValue()
            && $otherTimestamps[$this->node]->getValue() < $this->timestampMap[$this->node]->getValue()
        ) {
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

    /**
     * @throws UnComparableException
     */
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

    /**
     * @throws ClockIsNotIdleException
     */
    public function applyLocalEvent(): self
    {
        $this->ensureIsNotCommunicating();

        $this->incrementNode();

        return $this;
    }

    /**
     * @throws ClockIsNotIdleException
     * @throws UnknownNodeException
     */
    public function applySendEvent(string $node): self
    {
        $this->ensureIsNotCommunicating();

        $this->ensureNodeIsInVector($node);

        $this->communicatingNode = $node;
        $this->incrementNode();

        return $this;
    }

    /**
     * @throws UnexpectedReceiveEventException
     * @throws UnknownNodeException
     * @throws CannotReceiveSameClockInstanceException
     */
    public function applyReceiveEvent(self $clock): self
    {
        if ($this === $clock) {
            throw new CannotReceiveSameClockInstanceException();
        }

        if ($this->isCommunicating() && $this->communicatingNode !== $clock->getNode()) {
            /* @phpstan-ignore-next-line $this->communicatingNode cannot be null if $this->isCommunicating() is true */
            throw new UnexpectedReceiveEventException($this->communicatingNode, $clock->getNode());
        }

        $this->ensureNodeIsInVector($clock->getNode());

        if ($this->isCommunicating()) {
            $this->communicatingNode = null;
        } else {
            $this->incrementNode();
        }

        $this->mergeClock($clock);

        return $this;
    }

    public function addNode(string $node): bool
    {
        if ($this->isCommunicating()) {
            return false;
        }

        return parent::addNode($node);
    }

    public function removeNode(string $node): bool
    {
        if ($this->isCommunicating()) {
            return false;
        }

        return parent::removeNode($node);
    }

    public function getCommunicationState(): SyncClockState
    {
        return $this->communicatingNode ? SyncClockState::COMMUNICATING : SyncClockState::IDLE;
    }

    public function isIdle(): bool
    {
        return SyncClockState::IDLE === $this->getCommunicationState();
    }

    public function isCommunicating(): bool
    {
        return SyncClockState::COMMUNICATING === $this->getCommunicationState();
    }

    public function getCommunicatingNode(): ?string
    {
        return $this->communicatingNode;
    }

    /**
     * @throws ClockIsNotIdleException
     */
    private function ensureIsNotCommunicating(): void
    {
        if ($this->isCommunicating()) {
            throw new ClockIsNotIdleException();
        }
    }

    private function mergeClock(self $clock): void
    {
        $otherTimestampsMap = $clock->getTimestamps();

        foreach ($this->timestampMap as $currentNode => $currentTimestamp) {
            $otherTimestamps = $otherTimestampsMap[$currentNode] ?? null;
            if ($otherTimestamps && $otherTimestamps->getValue() > $currentTimestamp->getValue()) {
                $this->setVectorElementValue($currentNode, $otherTimestamps->getValue());
            }
        }
    }

    /**
     * Two clocks are comparable if they have the same nodes.
     */
    private function canBeCompared(SyncVectorClock $clock): bool
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
}
