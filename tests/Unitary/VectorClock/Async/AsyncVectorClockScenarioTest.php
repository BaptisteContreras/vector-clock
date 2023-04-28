<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\AsyncVectorClock;
use Dynamophp\VectorClock\ClockOrder;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class AsyncVectorClockScenarioTest extends TestCase
{
    private const PROCESS_1 = 'process 1';
    private const PROCESS_2 = 'process 2';
    private const PROCESS_3 = 'process 3';

    public function testPaperFigure3(): void
    {
        $clockProcess1 = new AsyncVectorClock(self::PROCESS_1);
        $clockProcess2 = new AsyncVectorClock(self::PROCESS_2);
        $clockProcess3 = new AsyncVectorClock(self::PROCESS_3);

        $clockProcess1->addNode(self::PROCESS_2);
        $clockProcess1->addNode(self::PROCESS_3);

        $clockProcess2->addNode(self::PROCESS_1);
        $clockProcess2->addNode(self::PROCESS_3);

        $clockProcess3->addNode(self::PROCESS_1);
        $clockProcess3->addNode(self::PROCESS_2);

        // Check that all vector must look like ['process 1' => 0, 'process 2' => 0, 'process 3' => 0]

        self::assertCount(3, $clockProcess1->getTimestamps());
        self::assertCount(3, $clockProcess2->getTimestamps());
        self::assertCount(3, $clockProcess3->getTimestamps());

        self::assertArrayHasKey(self::PROCESS_1, $clockProcess1->getTimestamps());
        self::assertArrayHasKey(self::PROCESS_2, $clockProcess1->getTimestamps());
        self::assertArrayHasKey(self::PROCESS_3, $clockProcess1->getTimestamps());

        self::assertArrayHasKey(self::PROCESS_1, $clockProcess2->getTimestamps());
        self::assertArrayHasKey(self::PROCESS_2, $clockProcess2->getTimestamps());
        self::assertArrayHasKey(self::PROCESS_3, $clockProcess2->getTimestamps());

        self::assertArrayHasKey(self::PROCESS_1, $clockProcess3->getTimestamps());
        self::assertArrayHasKey(self::PROCESS_2, $clockProcess3->getTimestamps());
        self::assertArrayHasKey(self::PROCESS_3, $clockProcess3->getTimestamps());

        // RA1 : Initially all values are zero

        foreach ($clockProcess1->getTimestamps() as $timestamp) {
            self::assertEquals(0, $timestamp->getValue());
        }

        foreach ($clockProcess2->getTimestamps() as $timestamp) {
            self::assertEquals(0, $timestamp->getValue());
        }

        foreach ($clockProcess3->getTimestamps() as $timestamp) {
            self::assertEquals(0, $timestamp->getValue());
        }

        $a = (clone $clockProcess1)->applySendEvent();
        $l = (clone $clockProcess2)->applyLocalEvent();
        $v = (clone $clockProcess3)->applyLocalEvent();

        // RA2 : The local clock value is incremented at least once before each atomic event.

        self::assertEquals(1, $a->getTimestamps()[self::PROCESS_1]->getValue());
        self::assertEquals(1, $l->getTimestamps()[self::PROCESS_2]->getValue());
        self::assertEquals(1, $v->getTimestamps()[self::PROCESS_3]->getValue());

        self::assertClock($a, 1, 0, 0);
        self::assertClock($l, 0, 1, 0);
        self::assertClock($v, 0, 0, 1);

        $b = (clone $a)->applyLocalEvent();
        $m = (clone $l)->applyReceiveEvent($a);
        $w = (clone $v)->applyLocalEvent();

        self::assertClock($b, 2, 0, 0);
        self::assertClock($m, 2, 2, 0);
        self::assertClock($w, 0, 0, 2);

        $c = (clone $b)->applySendEvent();
        $n = (clone $m)->applyReceiveEvent($w);
        $x = (clone $w)->applySendEvent();

        self::assertClock($c, 3, 0, 0);
        self::assertClock($n, 2, 3, 3);
        self::assertClock($x, 0, 0, 3);

        $d = (clone $c)->applyReceiveEvent($x);
        $o = (clone $n)->applySendEvent();
        $y = (clone $x)->applyLocalEvent();

        self::assertClock($d, 4, 0, 4);
        self::assertClock($o, 2, 4, 3);
        self::assertClock($y, 0, 0, 4);

        $p = (clone $o)->applyReceiveEvent($c);
        $z = (clone $y)->applyReceiveEvent($o);

        self::assertClock($p, 4, 5, 3);
        self::assertClock($z, 2, 5, 5);

        $q = (clone $p)->applyLocalEvent();

        self::assertClock($q, 4, 6, 3);

        // ASSERT HAPPEN BEFORE ON SAME PROCESS
        self::assertHappenBefore($w, $y); // w -> y
        self::assertHappenBefore($v, $w); // v -> w
        self::assertHappenBefore($v, $y); // v -> y

        self::assertHappenBefore($l, $p); // l -> p
        self::assertHappenBefore($p, $q); // p -> q
        self::assertHappenBefore($n, $o); // n -> o
        self::assertHappenBefore($o, $p); // o -> p

        self::assertHappenBefore($a, $d); // a -> d
        self::assertHappenBefore($b, $c); // b -> c
        self::assertHappenBefore($c, $d); // c -> d

        // ASSERT IDENTICAL
        self::assertIdentical($a, $a); // a == a
        self::assertIdentical($n, $n); // n == n
        self::assertIdentical($z, $z); // z == z
        self::assertIdentical($q, $q); // q == q
        self::assertIdentical($l, $l); // l == l

        // ASSERT HAPPEN BEFORE ON DIFFERENT PROCESS
        self::assertHappenBefore($b, $q); // b -> q
        self::assertHappenBefore($w, $n); // w -> n

        self::assertHappenBefore($a, $z); // a -> z
        self::assertHappenBefore($a, $m); // a -> m
        self::assertHappenBefore($a, $n); // a -> n
        self::assertHappenBefore($a, $o); // a -> o
        self::assertHappenBefore($a, $p); // a -> p
        self::assertHappenBefore($a, $q); // a -> q

        self::assertHappenBefore($b, $p); // b -> p
        self::assertHappenBefore($b, $q); // b -> q

        self::assertHappenBefore($c, $p); // c -> p
        self::assertHappenBefore($c, $q); // c -> q

        self::assertHappenBefore($l, $z); // l -> z

        self::assertHappenBefore($m, $z); // m -> z

        self::assertHappenBefore($n, $z); // n -> z

        self::assertHappenBefore($o, $z); // n -> z

        self::assertHappenBefore($v, $d); // v -> d
        self::assertHappenBefore($v, $n); // v -> n
        self::assertHappenBefore($v, $o); // v -> o
        self::assertHappenBefore($v, $p); // v -> p
        self::assertHappenBefore($v, $q); // v -> q

        self::assertHappenBefore($w, $d); // w -> d
        self::assertHappenBefore($w, $n); // w -> n
        self::assertHappenBefore($w, $o); // w -> o
        self::assertHappenBefore($w, $p); // w -> p
        self::assertHappenBefore($w, $q); // w -> q

        self::assertHappenBefore($x, $d); // x -> d

        // ASSERT CONCURRENT
        self::assertAreConcurrent($a, $l); // a <-> l
        self::assertAreConcurrent($a, $v); // a <-> v
        self::assertAreConcurrent($a, $w); // a <-> w
        self::assertAreConcurrent($a, $x); // a <-> x
        self::assertAreConcurrent($a, $y); // a <-> y

        self::assertAreConcurrent($b, $l); // b <-> l
        self::assertAreConcurrent($b, $o); // b <-> o
        self::assertAreConcurrent($b, $n); // b <-> n
        self::assertAreConcurrent($b, $m); // b <-> m
        self::assertAreConcurrent($b, $v); // b <-> v
        self::assertAreConcurrent($b, $w); // b <-> w
        self::assertAreConcurrent($b, $x); // b <-> x
        self::assertAreConcurrent($b, $y); // b <-> y
        self::assertAreConcurrent($b, $z); // b <-> z

        self::assertAreConcurrent($c, $l); // c <-> l
        self::assertAreConcurrent($c, $o); // c <-> o
        self::assertAreConcurrent($c, $n); // c <-> n
        self::assertAreConcurrent($c, $m); // c <-> m
        self::assertAreConcurrent($c, $v); // c <-> v
        self::assertAreConcurrent($c, $w); // c <-> w
        self::assertAreConcurrent($c, $x); // c <-> x
        self::assertAreConcurrent($c, $y); // c <-> y
        self::assertAreConcurrent($c, $z); // c <-> z

        self::assertAreConcurrent($d, $y); // d <-> y
        self::assertAreConcurrent($d, $z); // d <-> z
        self::assertAreConcurrent($d, $l); // d <-> l
        self::assertAreConcurrent($d, $m); // d <-> m
        self::assertAreConcurrent($d, $n); // d <-> n
        self::assertAreConcurrent($d, $o); // d <-> o
        self::assertAreConcurrent($d, $p); // d <-> p
        self::assertAreConcurrent($d, $q); // d <-> q

        self::assertAreConcurrent($l, $v); // l <-> v
        self::assertAreConcurrent($l, $w); // l <-> w
        self::assertAreConcurrent($l, $x); // l <-> x
        self::assertAreConcurrent($l, $y); // l <-> y

        self::assertAreConcurrent($m, $v); // m <-> v
        self::assertAreConcurrent($m, $w); // m <-> w
        self::assertAreConcurrent($m, $x); // m <-> x
        self::assertAreConcurrent($m, $y); // m <-> y

        self::assertAreConcurrent($n, $x); // n <-> x
        self::assertAreConcurrent($n, $y); // n <-> y

        self::assertAreConcurrent($o, $x); // o <-> x
        self::assertAreConcurrent($o, $y); // o <-> y

        self::assertAreConcurrent($p, $x); // p <-> x
        self::assertAreConcurrent($p, $y); // p <-> y
        self::assertAreConcurrent($p, $z); // p <-> z

        self::assertAreConcurrent($q, $x); // q <-> x
        self::assertAreConcurrent($q, $y); // q <-> y
        self::assertAreConcurrent($q, $z); // q <-> z
    }

    private static function assertClock(AsyncVectorClock $clock, int $process1, int $process2, int $process3): void
    {
        assertEquals($process1, $clock->getTimestamps()[self::PROCESS_1]->getValue());
        assertEquals($process2, $clock->getTimestamps()[self::PROCESS_2]->getValue());
        assertEquals($process3, $clock->getTimestamps()[self::PROCESS_3]->getValue());
    }

    private static function assertHappenBefore(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        // clock1 -> clock2

        assertTrue($clock1->happenBefore($clock2));
        assertFalse($clock1->happenAfter($clock2));
        assertEquals(ClockOrder::HAPPEN_BEFORE, $clock1->compare($clock2));

        assertFalse($clock2->happenBefore($clock1));
        assertTrue($clock2->happenAfter($clock1));
        assertEquals(ClockOrder::HAPPEN_AFTER, $clock2->compare($clock1));
    }

    private static function assertIdentical(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        // clock1 == clock2

        assertTrue($clock1->isIdenticalTo($clock2));
        assertTrue($clock2->isIdenticalTo($clock1));
        assertEquals(ClockOrder::IDENTICAL, $clock1->compare($clock2));
        assertEquals(ClockOrder::IDENTICAL, $clock2->compare($clock1));
    }

    private static function assertAreConcurrent(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        // clock1 <-> clock2

        assertTrue($clock1->isConcurrentWith($clock2));
        assertTrue($clock2->isConcurrentWith($clock1));
        assertEquals(ClockOrder::CONCURRENT, $clock1->compare($clock2));
        assertEquals(ClockOrder::CONCURRENT, $clock2->compare($clock1));
    }
}
