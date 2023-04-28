<?php

namespace Dynamophp\VectorClock\Test\Unitary\Lamport;

use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\LamportTimestamp;

class LamportTimestampScenarioTest extends AbstractLamportTimestampTest
{
    public function testPaperFigure1A(): void
    {
        $lt1 = new LamportTimestamp(76);
        $lt2 = new LamportTimestamp(59);

        self::assertTimestampValue(76, $lt1);
        self::assertTimestampValue(59, $lt2);
        self::assertHappenBefore($lt2, $lt1);

        $lt1->applyLocalEvent();
        $lt2->applyLocalEvent();

        self::assertTimestampValue(77, $lt1);
        self::assertTimestampValue(60, $lt2);
        self::assertHappenBefore($lt2, $lt1);

        $lt1ToSend = clone $lt1->applySendEvent();
        $lt2->applyLocalEvent();

        self::assertTimestampValue(78, $lt1);
        self::assertTimestampValue(78, $lt1ToSend);
        self::assertTrue($lt1->isIdenticalTo($lt1ToSend));
        self::assertEquals(ClockOrder::IDENTICAL, $lt1->compare($lt1ToSend));
        self::assertTimestampValue(61, $lt2);
        self::assertHappenBefore($lt2, $lt1);

        $lt1->applyLocalEvent();
        $lt2->applyReceiveEvent($lt1ToSend);

        self::assertTimestampValue(79, $lt1);
        self::assertTimestampValue(79, $lt2);
        self::assertTrue($lt1->isIdenticalTo($lt2));
        self::assertEquals(ClockOrder::IDENTICAL, $lt1->compare($lt2));

        $lt2->applyLocalEvent();

        self::assertTimestampValue(79, $lt1);
        self::assertTimestampValue(80, $lt2);
        self::assertHappenBefore($lt1, $lt2);
    }

    public function testPaperFigure1B(): void
    {
        $lt1 = new LamportTimestamp(54);
        $lt2 = new LamportTimestamp(59);

        self::assertTimestampValue(54, $lt1);
        self::assertTimestampValue(59, $lt2);
        self::assertHappenBefore($lt1, $lt2);

        $lt1->applyLocalEvent();
        $lt2->applyLocalEvent();

        self::assertTimestampValue(55, $lt1);
        self::assertTimestampValue(60, $lt2);
        self::assertHappenBefore($lt1, $lt2);

        $lt1ToSend = clone $lt1->applySendEvent();
        $lt2->applyLocalEvent();

        self::assertTimestampValue(56, $lt1);
        self::assertTimestampValue(56, $lt1ToSend);
        self::assertTrue($lt1->isIdenticalTo($lt1ToSend));
        self::assertEquals(ClockOrder::IDENTICAL, $lt1->compare($lt1ToSend));
        self::assertTimestampValue(61, $lt2);
        self::assertHappenBefore($lt1, $lt2);

        $lt1->applyLocalEvent();
        $lt2->applyReceiveEvent($lt1ToSend);
        self::assertTimestampValue(57, $lt1);
        self::assertTimestampValue(62, $lt2);
        self::assertHappenBefore($lt1, $lt2);

        $lt2->applyLocalEvent();
        self::assertTimestampValue(57, $lt1);
        self::assertTimestampValue(63, $lt2);
        self::assertHappenBefore($lt1, $lt2);
    }

    private static function assertHappenBefore(LamportTimestamp $lt1, LamportTimestamp $lt2): void
    {
        // $lt1 -> $lt2

        self::assertTrue($lt1->happenBefore($lt2));
        self::assertFalse($lt1->happenAfter($lt2));
        self::assertEquals(ClockOrder::HAPPEN_BEFORE, $lt1->compare($lt2));

        self::assertFalse($lt2->happenBefore($lt1));
        self::assertTrue($lt2->happenAfter($lt1));
        self::assertEquals(ClockOrder::HAPPEN_AFTER, $lt2->compare($lt1));
    }
}
