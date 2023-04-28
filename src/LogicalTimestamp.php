<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\InvalidInitValueException;

class LogicalTimestamp
{
    /**
     * @throws InvalidInitValueException
     */
    public function __construct(private readonly int $counter)
    {
        if (0 > $this->counter) {
            throw new InvalidInitValueException(self::class);
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
