<?php

namespace Dynamophp\VectorClock\Test\Unitary\Lamport;

use Dynamophp\VectorClock\Exception\CannotReceiveSameLamportTimestampInstanceException;
use Dynamophp\VectorClock\LamportTimestamp;
use PHPUnit\Framework\Attributes\DataProvider;

class LamportTimestampReceiveEventTest extends AbstractLamportTimestampTest
{
    #[DataProvider('provideReceiveData')]
    public function testApplyReceiveEvent(LamportTimestamp $lt1, LamportTimestamp $lt2, int $expectedValueForLt1): void
    {
        $oldLt2Value = $lt2->getValue();

        $lt1->applyReceiveEvent($lt2);

        self::assertTimestampValue($expectedValueForLt1, $lt1);
        self::assertTimestampValue($oldLt2Value, $lt2);
    }

    public function testApplyReceiveEventWithSameInstanceWhileIdleFails(): void
    {
        self::expectException(CannotReceiveSameLamportTimestampInstanceException::class);

        $lt = new LamportTimestamp(0);

        $lt->applyReceiveEvent($lt);
    }

    public static function provideReceiveData(): \Generator
    {
        yield '[0] <- [1] : [2]' => [new LamportTimestamp(0), new LamportTimestamp(1), 2];
        yield '[0] <- [2] : [3]' => [new LamportTimestamp(0), new LamportTimestamp(2), 3];
        yield '[1] <- [2] : [3]' => [new LamportTimestamp(1), new LamportTimestamp(2), 3];
        yield '[1] <- [5] : [6]' => [new LamportTimestamp(1), new LamportTimestamp(5), 6];
        yield '[0] <- [0] : [1]' => [new LamportTimestamp(0), new LamportTimestamp(0), 1];
        yield '[1] <- [1] : [2]' => [new LamportTimestamp(1), new LamportTimestamp(1), 2];
        yield '[99] <- [1] : [100]' => [new LamportTimestamp(99), new LamportTimestamp(1), 100];
        yield '[99] <- [80] : [100]' => [new LamportTimestamp(99), new LamportTimestamp(80), 100];
        yield '[99] <- [98] : [100]' => [new LamportTimestamp(99), new LamportTimestamp(98), 100];
        yield '[99] <- [100] : [101]' => [new LamportTimestamp(99), new LamportTimestamp(100), 101];
        yield '[99] <- [101] : [102]' => [new LamportTimestamp(99), new LamportTimestamp(101), 102];
        yield '[100] <- [100] : [101]' => [new LamportTimestamp(100), new LamportTimestamp(100), 101];
    }
}
