<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\CannotReceiveSameLamportTimestampInstanceException;
use Dynamophp\VectorClock\Exception\InvalidInitValueException;

class LamportTimestamp
{
    /**
     * @throws InvalidInitValueException
     */
    public function __construct(private int $value = 0)
    {
        if (0 > $this->value) {
            throw new InvalidInitValueException(self::class);
        }
    }

    public function compare(self $lt): ClockOrder
    {
        if ($this->value === $lt->getValue()) {
            return ClockOrder::IDENTICAL;
        }

        return $this->value < $lt->getValue() ? ClockOrder::HAPPEN_BEFORE : ClockOrder::HAPPEN_AFTER;
    }

    public function isIdenticalTo(self $lt): bool
    {
        return ClockOrder::IDENTICAL === $this->compare($lt);
    }

    public function happenBefore(self $lt): bool
    {
        return ClockOrder::HAPPEN_BEFORE === $this->compare($lt);
    }

    public function happenAfter(self $lt): bool
    {
        return ClockOrder::HAPPEN_AFTER === $this->compare($lt);
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
     * @throws CannotReceiveSameLamportTimestampInstanceException
     */
    public function applyReceiveEvent(self $lt): self
    {
        if ($this === $lt) {
            throw new CannotReceiveSameLamportTimestampInstanceException();
        }

        $this->value = max($this->value, $lt->getValue());

        $this->tick();

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    private function tick(): void
    {
        ++$this->value;
    }
}
