<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync;

use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\SyncVectorClock;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class SyncVectorClockScenarioTest extends AbstractSyncVectorTest
{
    private const PROCESS_1 = 'process 1';
    private const PROCESS_2 = 'process 2';
    private const PROCESS_3 = 'process 3';

    public function testPaperFigure7(): void
    {
        $clockProcess1 = new SyncVectorClock(self::PROCESS_1);
        $clockProcess2 = new SyncVectorClock(self::PROCESS_2);
        $clockProcess3 = new SyncVectorClock(self::PROCESS_3);

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

        $this->assertIsIdle($clockProcess1);
        $this->assertIsIdle($clockProcess2);
        $this->assertIsIdle($clockProcess3);

        $a = (clone $clockProcess1)->applyLocalEvent();
        $t = (clone $clockProcess3)->applyLocalEvent();

        // RA2 : The local clock value is incremented at least once before each atomic event.

        self::assertEquals(1, $a->getTimestamps()[self::PROCESS_1]->getValue());
        self::assertEquals(1, $t->getTimestamps()[self::PROCESS_3]->getValue());

        $this->assertClock($a, 1, 0, 0);
        $this->assertClock($t, 0, 0, 1);
        $this->assertIsIdle($clockProcess1);
        $this->assertIsIdle($clockProcess3);
        $this->assertIsIdle($a);
        $this->assertIsIdle($t);

        $b = clone $a;
        $bBis = clone $a;

        $l = clone $clockProcess2;
        $lBis = clone $clockProcess2;

        // Test the communication in both way
        $this->clocksSyncCommunication($b, $l);
        $this->clocksSyncCommunication($lBis, $bBis);

        $u = (clone $t)->applyLocalEvent();

        $this->assertClock($b, 2, 1, 0);
        $this->assertClock($bBis, 2, 1, 0);

        $this->assertClock($l, 2, 1, 0);
        $this->assertClock($lBis, 2, 1, 0);

        $this->assertClock($u, 0, 0, 2);

        $c = (clone $b)->applyLocalEvent();
        $m = (clone $l)->applyLocalEvent();
        $v = (clone $u)->applyLocalEvent();

        $this->assertClock($c, 3, 1, 0);
        $this->assertClock($m, 2, 2, 0);
        $this->assertClock($v, 0, 0, 3);

        $d = (clone $c)->applyLocalEvent();

        $n = clone $m;
        $nBis = clone $m;

        $w = clone $v;
        $wBis = clone $v;

        // Test the communication in both way
        $this->clocksSyncCommunication($n, $w);
        $this->clocksSyncCommunication($wBis, $nBis);

        $this->assertClock($d, 4, 1, 0);

        $this->assertClock($n, 2, 3, 4);
        $this->assertClock($nBis, 2, 3, 4);

        $this->assertClock($w, 2, 3, 4);
        $this->assertClock($wBis, 2, 3, 4);

        $o = clone $n;
        $oBis = clone $n;

        $x = clone $w;
        $xBis = clone $w;

        // Test the communication in both way
        $this->clocksSyncCommunication($x, $o);
        $this->clocksSyncCommunication($oBis, $xBis);

        $this->assertClock($o, 2, 4, 5);
        $this->assertClock($oBis, 2, 4, 5);

        $this->assertClock($x, 2, 4, 5);
        $this->assertClock($xBis, 2, 4, 5);

        $e = clone $d;
        $eBis = clone $d;

        $p = clone $o;
        $pBis = clone $o;

        // Test the communication in both way
        $this->clocksSyncCommunication($e, $p);
        $this->clocksSyncCommunication($pBis, $eBis);

        $this->assertClock($e, 5, 5, 5);
        $this->assertClock($eBis, 5, 5, 5);

        $this->assertClock($p, 5, 5, 5);
        $this->assertClock($pBis, 5, 5, 5);

        $f = (clone $e)->applyLocalEvent();
        $y = (clone $x)->applyLocalEvent();

        $this->assertClock($f, 6, 5, 5);
        $this->assertClock($y, 2, 4, 6);

        $g = clone $f;
        $gBis = clone $f;

        $z = clone $y;
        $zBis = clone $y;

        // Test the communication in both way
        $this->clocksSyncCommunication($g, $z);
        $this->clocksSyncCommunication($zBis, $gBis);

        $this->assertClock($g, 7, 5, 7);
        $this->assertClock($gBis, 7, 5, 7);

        $this->assertClock($z, 7, 5, 7);
        $this->assertClock($zBis, 7, 5, 7);

        // ASSERT HAPPEN BEFORE ON SAME PROCESS
        $this->assertHappenBefore($a, $b); // a -> b
        $this->assertHappenBefore($b, $c); // b -> c
        $this->assertHappenBefore($c, $d); // c -> d
        $this->assertHappenBefore($d, $e); // d -> e
        $this->assertHappenBefore($e, $f); // e -> f
        $this->assertHappenBefore($f, $g); // f -> g

        $this->assertHappenBefore($l, $m); // l -> m
        $this->assertHappenBefore($m, $n); // m -> n
        $this->assertHappenBefore($n, $o); // n -> o
        $this->assertHappenBefore($o, $p); // o -> p

        $this->assertHappenBefore($t, $u); // t -> u
        $this->assertHappenBefore($u, $v); // u -> v
        $this->assertHappenBefore($v, $w); // v -> w
        $this->assertHappenBefore($w, $x); // w -> x
        $this->assertHappenBefore($x, $y); // x -> y
        $this->assertHappenBefore($y, $z); // y -> z

        // ASSERT IDENTICAL
        $this->assertIdentical($a, $a); // a == a
        $this->assertIdentical($b, $b); // b == b
        $this->assertIdentical($l, $l); // l == l
        $this->assertIdentical($b, $l); // b == l
        $this->assertIdentical($n, $w); // n == w
        $this->assertIdentical($o, $x); // o == x
        $this->assertIdentical($e, $p); // e == p
        $this->assertIdentical($g, $z); // g == z

        // Tep[p] > Tfq[p] && Tfq[q] > Tep[q]
        // ||
        // Tep[q] >= Tfq[q] && Tfq[p] >= Tep[p]
        // ASSERT HAPPEN BEFORE ON DIFFERENT PROCESS
        $this->assertHappenBefore($a, $l); // a -> l
        $this->assertHappenBefore($a, $m); // a -> m
        $this->assertHappenBefore($a, $n); // a -> n
        $this->assertHappenBefore($a, $o); // a -> o
        $this->assertHappenBefore($a, $p); // a -> p
        $this->assertHappenBefore($a, $w); // a -> q
        $this->assertHappenBefore($a, $x); // a -> x
        $this->assertHappenBefore($a, $y); // a -> y
        $this->assertHappenBefore($a, $z); // a -> z

        $this->assertHappenBefore($b, $m); // b -> m
        $this->assertHappenBefore($b, $n); // b -> n
        $this->assertHappenBefore($b, $o); // b -> o
        $this->assertHappenBefore($b, $p); // b -> p
        $this->assertHappenBefore($b, $w); // b -> q
        $this->assertHappenBefore($b, $x); // b -> x
        $this->assertHappenBefore($b, $y); // b -> y
        $this->assertHappenBefore($b, $z); // b -> z

        $this->assertHappenBefore($c, $p); // c -> p
        $this->assertHappenBefore($c, $z); // c -> z

        $this->assertHappenBefore($d, $p); // d -> p
        $this->assertHappenBefore($d, $z); // d -> z

        $this->assertHappenBefore($e, $z); // e -> z

        $this->assertHappenBefore($f, $z); // f -> z

        $this->assertHappenBefore($l, $e); // l -> e
        $this->assertHappenBefore($l, $f); // l -> f
        $this->assertHappenBefore($l, $g); // l -> g
        $this->assertHappenBefore($l, $w); // l -> w
        $this->assertHappenBefore($l, $x); // l -> x
        $this->assertHappenBefore($l, $y); // l -> y
        $this->assertHappenBefore($l, $z); // l -> z

        $this->assertHappenBefore($m, $e); // m -> e
        $this->assertHappenBefore($m, $f); // m -> f
        $this->assertHappenBefore($m, $g); // m -> g
        $this->assertHappenBefore($m, $w); // m -> w
        $this->assertHappenBefore($m, $x); // m -> x
        $this->assertHappenBefore($m, $y); // m -> y
        $this->assertHappenBefore($m, $z); // m -> z

        $this->assertHappenBefore($n, $e); // n -> e
        $this->assertHappenBefore($n, $f); // n -> f
        $this->assertHappenBefore($n, $g); // n -> g
        $this->assertHappenBefore($n, $x); // n -> x
        $this->assertHappenBefore($n, $y); // n -> y
        $this->assertHappenBefore($n, $z); // n -> z

        $this->assertHappenBefore($o, $e); // o -> e
        $this->assertHappenBefore($o, $f); // o -> f
        $this->assertHappenBefore($o, $g); // o -> g
        $this->assertHappenBefore($o, $y); // o -> y
        $this->assertHappenBefore($o, $z); // o -> z

        $this->assertHappenBefore($p, $f); // p -> f
        $this->assertHappenBefore($p, $g); // p -> g
        $this->assertHappenBefore($p, $z); // p -> z

        $this->assertHappenBefore($t, $n); // t -> n
        $this->assertHappenBefore($t, $o); // t -> o
        $this->assertHappenBefore($t, $p); // t -> p
        $this->assertHappenBefore($t, $e); // t -> e
        $this->assertHappenBefore($t, $f); // t -> f
        $this->assertHappenBefore($t, $g); // t -> g

        $this->assertHappenBefore($u, $n); // u -> n
        $this->assertHappenBefore($u, $o); // u -> o
        $this->assertHappenBefore($u, $p); // u -> p
        $this->assertHappenBefore($u, $e); // u -> e
        $this->assertHappenBefore($u, $f); // u -> f
        $this->assertHappenBefore($u, $g); // u -> g

        $this->assertHappenBefore($v, $n); // v -> n
        $this->assertHappenBefore($v, $o); // v -> o
        $this->assertHappenBefore($v, $p); // v -> p
        $this->assertHappenBefore($v, $e); // v -> e
        $this->assertHappenBefore($v, $f); // v -> f
        $this->assertHappenBefore($v, $g); // v -> g

        $this->assertHappenBefore($w, $o); // w -> o
        $this->assertHappenBefore($w, $p); // w -> p
        $this->assertHappenBefore($w, $e); // w -> e
        $this->assertHappenBefore($w, $f); // w -> f
        $this->assertHappenBefore($w, $g); // w -> g

        $this->assertHappenBefore($x, $p); // w -> p
        $this->assertHappenBefore($x, $e); // w -> e
        $this->assertHappenBefore($x, $f); // w -> f
        $this->assertHappenBefore($x, $g); // w -> g

        $this->assertHappenBefore($y, $g); // y -> g

        // Tep[p] > Tfq[p] && Tfq[q] > Tep[q]
        // ||
        // Tep[q] >= Tfq[q] && Tfq[p] >= Tep[p]

        // ASSERT CONCURRENT

        $this->assertAreConcurrent($a, $t); // a <-> t
        $this->assertAreConcurrent($a, $u); // a <-> u
        $this->assertAreConcurrent($a, $v); // a <-> v

        $this->assertAreConcurrent($b, $t); // b <-> t
        $this->assertAreConcurrent($b, $u); // b <-> u
        $this->assertAreConcurrent($b, $v); // b <-> v

        $this->assertAreConcurrent($c, $t); // c <-> t
        $this->assertAreConcurrent($c, $u); // c <-> u
        $this->assertAreConcurrent($c, $v); // c <-> v
        $this->assertAreConcurrent($c, $m); // c <-> m
        $this->assertAreConcurrent($c, $n); // c <-> n
        $this->assertAreConcurrent($c, $o); // c <-> o
        $this->assertAreConcurrent($c, $w); // c <-> w
        $this->assertAreConcurrent($c, $x); // c <-> x
        $this->assertAreConcurrent($c, $y); // c <-> y

        $this->assertAreConcurrent($d, $t); // d <-> t
        $this->assertAreConcurrent($d, $u); // d <-> u
        $this->assertAreConcurrent($d, $v); // d <-> v
        $this->assertAreConcurrent($d, $m); // d <-> m
        $this->assertAreConcurrent($d, $n); // d <-> n
        $this->assertAreConcurrent($d, $o); // d <-> o
        $this->assertAreConcurrent($d, $w); // d <-> w
        $this->assertAreConcurrent($d, $x); // d <-> x
        $this->assertAreConcurrent($d, $y); // d <-> y

        $this->assertAreConcurrent($e, $y); // e <-> y

        $this->assertAreConcurrent($l, $t); // l <-> t
        $this->assertAreConcurrent($l, $u); // l <-> u
        $this->assertAreConcurrent($l, $v); // l <-> v

        $this->assertAreConcurrent($m, $t); // m <-> t
        $this->assertAreConcurrent($m, $u); // m <-> u
        $this->assertAreConcurrent($m, $v); // m <-> v

        $this->assertAreConcurrent($p, $y); // p <-> y
    }

    private function assertClock(SyncVectorClock $clock, int $process1, int $process2, int $process3): void
    {
        assertEquals($process1, $clock->getTimestamps()[self::PROCESS_1]->getValue());
        assertEquals($process2, $clock->getTimestamps()[self::PROCESS_2]->getValue());
        assertEquals($process3, $clock->getTimestamps()[self::PROCESS_3]->getValue());
        $this->assertIsIdle($clock);
    }

    private function assertHappenBefore(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // clock1 -> clock2

        assertTrue($clock1->happenBefore($clock2));
        assertFalse($clock1->happenAfter($clock2));
        assertEquals(ClockOrder::HAPPEN_BEFORE, $clock1->compare($clock2));

        assertFalse($clock2->happenBefore($clock1));
        assertTrue($clock2->happenAfter($clock1));
        assertEquals(ClockOrder::HAPPEN_AFTER, $clock2->compare($clock1));
    }

    private function assertIdentical(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // clock1 == clock2

        assertTrue($clock1->isIdenticalTo($clock2));
        assertTrue($clock2->isIdenticalTo($clock1));
        assertEquals(ClockOrder::IDENTICAL, $clock1->compare($clock2));
        assertEquals(ClockOrder::IDENTICAL, $clock2->compare($clock1));
    }

    private function assertAreConcurrent(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // clock1 <-> clock2

        assertTrue($clock1->isConcurrentWith($clock2));
        assertTrue($clock2->isConcurrentWith($clock1));
        assertEquals(ClockOrder::CONCURRENT, $clock1->compare($clock2));
        assertEquals(ClockOrder::CONCURRENT, $clock2->compare($clock1));
    }

    private function clocksSyncCommunication(SyncVectorClock $clockSender, SyncVectorClock $clockReceiver): void
    {
        $this->assertIsIdle($clockSender);
        $this->assertIsIdle($clockReceiver);

        $clockSender->applySendEvent($clockReceiver->getNode());
        $this->assertIsCommunicating($clockSender, $clockReceiver->getNode());
        $this->assertIsIdle($clockReceiver);

        $clockReceiver->applyReceiveEvent($clockSender);
        $this->assertIsCommunicating($clockSender, $clockReceiver->getNode());
        $this->assertIsIdle($clockReceiver);

        $clockSender->applyReceiveEvent($clockReceiver);

        $this->assertIsIdle($clockSender);
        $this->assertIsIdle($clockReceiver);
    }
}
