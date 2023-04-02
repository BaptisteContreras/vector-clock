<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\InvalidLogicalTimestampValueException;

class LogicalTimestamp
{
    /**
     * @throws InvalidLogicalTimestampValueException
     */
    public function __construct(private readonly int $counter)
    {
        if ($this->counter < 0) {
            throw new InvalidLogicalTimestampValueException();
        }
    }

    public static function init(): self
    {
        return new self(0);
    }

    public function getValue(): int
    {
        return $this->counter;
    }

    public function increment(): self
    {
        return new self($this->counter + 1);
    }

    public function isEqualTo(LogicalTimestamp $logicalTimestamp): bool
    {
        return $this->counter === $logicalTimestamp->getValue();
    }
}
