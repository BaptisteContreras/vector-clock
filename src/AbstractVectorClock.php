<?php

namespace Dynamophp\VectorClock;

use Dynamophp\VectorClock\Exception\InvalidNodeNameException;
use Dynamophp\VectorClock\Exception\InvalidVectorClockStateException;
use Dynamophp\VectorClock\Exception\NumericNodeNameException;
use Dynamophp\VectorClock\Exception\UnknownNodeException;

abstract class AbstractVectorClock
{
    /**
     * @param array<string, LogicalTimestamp> $timestamps
     *
     * @throws NumericNodeNameException
     * @throws InvalidNodeNameException
     * @throws InvalidVectorClockStateException
     */
    public function __construct(protected readonly string $node, protected array $timestamps = [])
    {
        if (!isset($this->timestamps[$this->node])) {
            $this->timestamps[$this->node] = LogicalTimestamp::init();
        }

        $this->validateInitialState();
    }

    /**
     * @throws InvalidNodeNameException
     * @throws NumericNodeNameException
     */
    public function addNode(string $node): bool
    {
        $this->validateNode($node);

        if (!isset($this->timestamps[$node])) {
            $this->timestamps[$node] = LogicalTimestamp::init();

            return true;
        }

        return false;
    }

    public function removeNode(string $node): bool
    {
        if ($node !== $this->node && $this->isNodeInVector($node)) {
            unset($this->timestamps[$node]);

            return true;
        }

        return false;
    }

    public function getNode(): string
    {
        return $this->node;
    }

    /**
     * @return array<string, LogicalTimestamp>
     */
    public function getTimestamps(): array
    {
        return $this->timestamps;
    }

    /**
     * @throws InvalidNodeNameException
     * @throws NumericNodeNameException
     */
    protected function validateNode(mixed $node): void
    {
        if (is_numeric($node)) {
            throw new NumericNodeNameException();
        }

        if (!is_string($node) || '' === $node) {
            throw new InvalidNodeNameException();
        }
    }

    /**
     * @throws InvalidNodeNameException
     * @throws InvalidVectorClockStateException
     * @throws NumericNodeNameException
     */
    protected function validateInitialState(): void
    {
        foreach ($this->timestamps as $node => $timestamp) {
            $this->validateNode($node);

            if (!$timestamp instanceof LogicalTimestamp) {
                throw new InvalidVectorClockStateException();
            }
        }
    }

    protected function tick(): void
    {
        $this->incrementNodeValue($this->node);
    }

    protected function incrementNodeValue(string $node): void
    {
        $this->timestamps[$node] = $this->timestamps[$node]->increment();
    }

    protected function setNodeValue(string $node, int $value): void
    {
        $this->timestamps[$node] = new LogicalTimestamp($value);
    }

    protected function isNodeInVector(string $node): bool
    {
        return isset($this->timestamps[$node]);
    }

    /**
     * @throws UnknownNodeException
     */
    protected function ensureNodeIsInVector(string $node): void
    {
        if (!$this->isNodeInVector($node)) {
            throw new UnknownNodeException($node);
        }
    }
}
