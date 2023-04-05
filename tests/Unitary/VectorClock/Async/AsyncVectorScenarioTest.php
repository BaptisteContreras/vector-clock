<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\AsyncVectorClock;
use Dynamophp\VectorClock\ClockOrder;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class AsyncVectorScenarioTest extends TestCase
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

        $this->assertClock($a, 1, 0, 0);
        $this->assertClock($l, 0, 1, 0);
        $this->assertClock($v, 0, 0, 1);

        $b = (clone $a)->applyLocalEvent();
        $m = (clone $l)->applyReceiveEvent($a);
        $w = (clone $v)->applyLocalEvent();

        $this->assertClock($b, 2, 0, 0);
        $this->assertClock($m, 2, 2, 0);
        $this->assertClock($w, 0, 0, 2);

        $c = (clone $b)->applySendEvent();
        $n = (clone $m)->applyReceiveEvent($w);
        $x = (clone $w)->applySendEvent();

        $this->assertClock($c, 3, 0, 0);
        $this->assertClock($n, 2, 3, 3);
        $this->assertClock($x, 0, 0, 3);

        $d = (clone $c)->applyReceiveEvent($x);
        $o = (clone $n)->applySendEvent();
        $y = (clone $x)->applyLocalEvent();

        $this->assertClock($d, 4, 0, 4);
        $this->assertClock($o, 2, 4, 3);
        $this->assertClock($y, 0, 0, 4);

        $p = (clone $o)->applyReceiveEvent($c);
        $z = (clone $y)->applyReceiveEvent($o);

        $this->assertClock($p, 4, 5, 3);
        $this->assertClock($z, 2, 5, 5);

        $q = (clone $p)->applyLocalEvent();

        $this->assertClock($q, 4, 6, 3);

        // ASSERT HAPPEN BEFORE ON SAME PROCESS
        $this->assertHappenBefore($w, $y); // w -> y
        $this->assertHappenBefore($v, $w); // v -> w
        $this->assertHappenBefore($v, $y); // v -> y

        $this->assertHappenBefore($l, $p); // l -> p
        $this->assertHappenBefore($p, $q); // p -> q
        $this->assertHappenBefore($n, $o); // n -> o
        $this->assertHappenBefore($o, $p); // o -> p

        $this->assertHappenBefore($a, $d); // a -> d
        $this->assertHappenBefore($b, $c); // b -> c
        $this->assertHappenBefore($c, $d); // c -> d

        // ASSERT IDENTICAL
        $this->assertIdentical($a, $a); // a == a
        $this->assertIdentical($n, $n); // n == n
        $this->assertIdentical($z, $z); // z == z
        $this->assertIdentical($q, $q); // q == q
        $this->assertIdentical($l, $l); // l == l

        // ASSERT HAPPEN BEFORE ON DIFFERENT PROCESS
        $this->assertHappenBefore($b, $q); // b -> q
        $this->assertHappenBefore($w, $n); // w -> n

        $this->assertHappenBefore($a, $z); // a -> z
        $this->assertHappenBefore($a, $m); // a -> m
        $this->assertHappenBefore($a, $n); // a -> n
        $this->assertHappenBefore($a, $o); // a -> o
        $this->assertHappenBefore($a, $p); // a -> p
        $this->assertHappenBefore($a, $q); // a -> q

        $this->assertHappenBefore($b, $p); // b -> p
        $this->assertHappenBefore($b, $q); // b -> q

        $this->assertHappenBefore($c, $p); // c -> p
        $this->assertHappenBefore($c, $q); // c -> q

        $this->assertHappenBefore($l, $z); // l -> z

        $this->assertHappenBefore($m, $z); // m -> z

        $this->assertHappenBefore($n, $z); // n -> z

        $this->assertHappenBefore($o, $z); // n -> z

        $this->assertHappenBefore($v, $d); // v -> d
        $this->assertHappenBefore($v, $n); // v -> n
        $this->assertHappenBefore($v, $o); // v -> o
        $this->assertHappenBefore($v, $p); // v -> p
        $this->assertHappenBefore($v, $q); // v -> q

        $this->assertHappenBefore($w, $d); // w -> d
        $this->assertHappenBefore($w, $n); // w -> n
        $this->assertHappenBefore($w, $o); // w -> o
        $this->assertHappenBefore($w, $p); // w -> p
        $this->assertHappenBefore($w, $q); // w -> q

        $this->assertHappenBefore($x, $d); // x -> d

        // ASSERT CONCURRENT
        $this->assertAreConcurrent($a, $l); // a <-> l
        $this->assertAreConcurrent($a, $v); // a <-> v
        $this->assertAreConcurrent($a, $w); // a <-> w
        $this->assertAreConcurrent($a, $x); // a <-> x
        $this->assertAreConcurrent($a, $y); // a <-> y

        $this->assertAreConcurrent($b, $l); // b <-> l
        $this->assertAreConcurrent($b, $o); // b <-> o
        $this->assertAreConcurrent($b, $n); // b <-> n
        $this->assertAreConcurrent($b, $m); // b <-> m
        $this->assertAreConcurrent($b, $v); // b <-> v
        $this->assertAreConcurrent($b, $w); // b <-> w
        $this->assertAreConcurrent($b, $x); // b <-> x
        $this->assertAreConcurrent($b, $y); // b <-> y
        $this->assertAreConcurrent($b, $z); // b <-> z

        $this->assertAreConcurrent($c, $l); // c <-> l
        $this->assertAreConcurrent($c, $o); // c <-> o
        $this->assertAreConcurrent($c, $n); // c <-> n
        $this->assertAreConcurrent($c, $m); // c <-> m
        $this->assertAreConcurrent($c, $v); // c <-> v
        $this->assertAreConcurrent($c, $w); // c <-> w
        $this->assertAreConcurrent($c, $x); // c <-> x
        $this->assertAreConcurrent($c, $y); // c <-> y
        $this->assertAreConcurrent($c, $z); // c <-> z

        $this->assertAreConcurrent($d, $y); // d <-> y
        $this->assertAreConcurrent($d, $z); // d <-> z
        $this->assertAreConcurrent($d, $l); // d <-> l
        $this->assertAreConcurrent($d, $m); // d <-> m
        $this->assertAreConcurrent($d, $n); // d <-> n
        $this->assertAreConcurrent($d, $o); // d <-> o
        $this->assertAreConcurrent($d, $p); // d <-> p
        $this->assertAreConcurrent($d, $q); // d <-> q

        $this->assertAreConcurrent($l, $v); // l <-> v
        $this->assertAreConcurrent($l, $w); // l <-> w
        $this->assertAreConcurrent($l, $x); // l <-> x
        $this->assertAreConcurrent($l, $y); // l <-> y

        $this->assertAreConcurrent($m, $v); // m <-> v
        $this->assertAreConcurrent($m, $w); // m <-> w
        $this->assertAreConcurrent($m, $x); // m <-> x
        $this->assertAreConcurrent($m, $y); // m <-> y

        $this->assertAreConcurrent($n, $x); // n <-> x
        $this->assertAreConcurrent($n, $y); // n <-> y

        $this->assertAreConcurrent($o, $x); // o <-> x
        $this->assertAreConcurrent($o, $y); // o <-> y

        $this->assertAreConcurrent($p, $x); // p <-> x
        $this->assertAreConcurrent($p, $y); // p <-> y
        $this->assertAreConcurrent($p, $z); // p <-> z

        $this->assertAreConcurrent($q, $x); // q <-> x
        $this->assertAreConcurrent($q, $y); // q <-> y
        $this->assertAreConcurrent($q, $z); // q <-> z
    }

    private function assertClock(AsyncVectorClock $clock, int $process1, int $process2, int $process3): void
    {
        assertEquals($process1, $clock->getTimestamps()[self::PROCESS_1]->getValue());
        assertEquals($process2, $clock->getTimestamps()[self::PROCESS_2]->getValue());
        assertEquals($process3, $clock->getTimestamps()[self::PROCESS_3]->getValue());
    }

    private function assertHappenBefore(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        // clock1 -> clock2

        assertTrue($clock1->happenBefore($clock2));
        assertFalse($clock1->happenAfter($clock2));
        assertEquals(ClockOrder::HAPPEN_BEFORE, $clock1->compare($clock2));

        assertFalse($clock2->happenBefore($clock1));
        assertTrue($clock2->happenAfter($clock1));
        assertEquals(ClockOrder::HAPPEN_AFTER, $clock2->compare($clock1));
    }

    private function assertIdentical(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        // clock1 == clock2

        assertTrue($clock1->isIdenticalTo($clock2));
        assertTrue($clock2->isIdenticalTo($clock1));
        assertEquals(ClockOrder::IDENTICAL, $clock1->compare($clock2));
        assertEquals(ClockOrder::IDENTICAL, $clock2->compare($clock1));
    }

    private function assertAreConcurrent(AsyncVectorClock $clock1, AsyncVectorClock $clock2): void
    {
        // clock1 <-> clock2

        assertTrue($clock1->isConcurrentWith($clock2));
        assertTrue($clock2->isConcurrentWith($clock1));
        assertEquals(ClockOrder::CONCURRENT, $clock1->compare($clock2));
        assertEquals(ClockOrder::CONCURRENT, $clock2->compare($clock1));
    }
}
