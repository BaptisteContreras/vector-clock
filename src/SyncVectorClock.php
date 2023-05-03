<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\CannotReceiveSameClockInstanceException;
use Dynamophp\VectorClock\Exception\ClockIsNotIdleException;
use Dynamophp\VectorClock\Exception\IncomparableException;
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

        $otherClockTimestamps = $clock->getTimestamps();

        if ($this->timestamps[$this->node]->getValue() <= $otherClockTimestamps[$this->node]->getValue()
            && $this->timestamps[$clock->getNode()]->getValue() < $otherClockTimestamps[$clock->getNode()]->getValue()
        ) {
            return ClockOrder::HAPPEN_BEFORE;
        }

        if ($otherClockTimestamps[$clock->getNode()]->getValue() <= $this->timestamps[$clock->getNode()]->getValue()
            && $otherClockTimestamps[$this->node]->getValue() < $this->timestamps[$this->node]->getValue()
        ) {
            return ClockOrder::HAPPEN_AFTER;
        }

        $hasDifferentTimestamp = false;

        foreach ($this->timestamps as $currentNode => $currentTimestamps) {
            if (!$currentTimestamps->isEqualTo($otherClockTimestamps[$currentNode])) {
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

    /**
     * @throws ClockIsNotIdleException
     */
    public function applyLocalEvent(): self
    {
        $this->ensureIsNotCommunicating();

        $this->tick();

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
        $this->tick();

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
            $this->tick();
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
        $otherClockTimestamps = $clock->getTimestamps();

        foreach ($this->timestamps as $currentNode => $currentTimestamp) {
            $otherClockCurrentTimestamp = $otherClockTimestamps[$currentNode] ?? null;
            if ($otherClockCurrentTimestamp && $otherClockCurrentTimestamp->getValue() > $currentTimestamp->getValue()) {
                $this->setNodeValue($currentNode, $otherClockCurrentTimestamp->getValue());
            }
        }
    }

    /**
     * Two clocks are comparable if they have the same nodes.
     */
    private function canBeCompared(SyncVectorClock $clock): bool
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
}
