<?php

namespace Dynamophp\VectorClock\Test\Unitary\Lamport;

use Dynamophp\VectorClock\Exception\InvalidInitValueException;
use Dynamophp\VectorClock\LamportTimestamp;
use PHPUnit\Framework\Attributes\DataProvider;

class LamportTimestampCreationTest extends AbstractLamportTimestampTest
{
    #[DataProvider('provideGoodInitValues')]
    public function testCreationSucceed(int|float $initValue): void
    {
        $ltimestamp = new LamportTimestamp($initValue);

        self::assertEquals((int) $initValue, $ltimestamp->getValue());
    }

    #[DataProvider('provideBadInitValues')]
    public function testCreationFails(int|float $initValue): void
    {
        self::expectException(InvalidInitValueException::class);
        $ltimestamp = new LamportTimestamp($initValue);
    }

    public static function provideGoodInitValues(): \Generator
    {
        yield [0];
        yield [1];
        yield [2];
        yield [10];
        yield [99];
        yield [100];
        yield [1000];
        yield [10000];
        yield [100000];
        yield [1000000];
        yield [10000000];
        yield [100000000];
        yield [1000000000];
        yield [10000000000];
        yield [100000000000];
        yield [1000000000000];
        yield [10000000000000];
        yield [0.0];
        yield [1.0];
        yield [2.0];
        yield [10.0];
        yield [99.0];
        yield [100.0];
        yield [1000.0];
        yield [10000.0];
        yield [100000.0];
        yield [1000000.0];
        yield [10000000.0];
        yield [100000000.0];
        yield [1000000000.0];
        yield [10000000000.0];
        yield [100000000000.0];
        yield [1000000000000.0];
        yield [10000000000000.0];
    }

    public static function provideBadInitValues(): \Generator
    {
        yield [-1];
        yield [-2];
        yield [-10];
        yield [-99];
        yield [-100];
        yield [-1000];
        yield [-10000];
        yield [-100000];
        yield [-1000000];
        yield [-10000000];
        yield [-100000000];
        yield [-1000000000];
        yield [-10000000000];
        yield [-100000000000];
        yield [-1000000000000];
        yield [-10000000000000];
        yield [-1.0];
        yield [-2.0];
        yield [-10.0];
        yield [-99.0];
        yield [-100.0];
        yield [-1000.0];
        yield [-10000.0];
        yield [-100000.0];
        yield [-1000000.0];
        yield [-10000000.0];
        yield [-100000000.0];
        yield [-1000000000.0];
        yield [-10000000000.0];
        yield [-100000000000.0];
        yield [-1000000000000.0];
        yield [-10000000000000.0];
    }
}
