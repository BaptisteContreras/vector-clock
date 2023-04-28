<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Sync;

use Dynamophp\VectorClock\ClockOrder;
use Dynamophp\VectorClock\SyncVectorClock;

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

        self::assertIsIdle($clockProcess1);
        self::assertIsIdle($clockProcess2);
        self::assertIsIdle($clockProcess3);

        $a = (clone $clockProcess1)->applyLocalEvent();
        $t = (clone $clockProcess3)->applyLocalEvent();

        // RA2 : The local clock value is incremented at least once before each atomic event.

        self::assertEquals(1, $a->getTimestamps()[self::PROCESS_1]->getValue());
        self::assertEquals(1, $t->getTimestamps()[self::PROCESS_3]->getValue());

        self::assertClock($a, 1, 0, 0);
        self::assertClock($t, 0, 0, 1);
        self::assertIsIdle($clockProcess1);
        self::assertIsIdle($clockProcess3);
        self::assertIsIdle($a);
        self::assertIsIdle($t);

        $b = clone $a;
        $bBis = clone $a;

        $l = clone $clockProcess2;
        $lBis = clone $clockProcess2;

        // Test the communication in both way
        $this->clocksSyncCommunication($b, $l);
        $this->clocksSyncCommunication($lBis, $bBis);

        $u = (clone $t)->applyLocalEvent();

        self::assertClock($b, 2, 1, 0);
        self::assertClock($bBis, 2, 1, 0);

        self::assertClock($l, 2, 1, 0);
        self::assertClock($lBis, 2, 1, 0);

        self::assertClock($u, 0, 0, 2);

        $c = (clone $b)->applyLocalEvent();
        $m = (clone $l)->applyLocalEvent();
        $v = (clone $u)->applyLocalEvent();

        self::assertClock($c, 3, 1, 0);
        self::assertClock($m, 2, 2, 0);
        self::assertClock($v, 0, 0, 3);

        $d = (clone $c)->applyLocalEvent();

        $n = clone $m;
        $nBis = clone $m;

        $w = clone $v;
        $wBis = clone $v;

        // Test the communication in both way
        $this->clocksSyncCommunication($n, $w);
        $this->clocksSyncCommunication($wBis, $nBis);

        self::assertClock($d, 4, 1, 0);

        self::assertClock($n, 2, 3, 4);
        self::assertClock($nBis, 2, 3, 4);

        self::assertClock($w, 2, 3, 4);
        self::assertClock($wBis, 2, 3, 4);

        $o = clone $n;
        $oBis = clone $n;

        $x = clone $w;
        $xBis = clone $w;

        // Test the communication in both way
        $this->clocksSyncCommunication($x, $o);
        $this->clocksSyncCommunication($oBis, $xBis);

        self::assertClock($o, 2, 4, 5);
        self::assertClock($oBis, 2, 4, 5);

        self::assertClock($x, 2, 4, 5);
        self::assertClock($xBis, 2, 4, 5);

        $e = clone $d;
        $eBis = clone $d;

        $p = clone $o;
        $pBis = clone $o;

        // Test the communication in both way
        $this->clocksSyncCommunication($e, $p);
        $this->clocksSyncCommunication($pBis, $eBis);

        self::assertClock($e, 5, 5, 5);
        self::assertClock($eBis, 5, 5, 5);

        self::assertClock($p, 5, 5, 5);
        self::assertClock($pBis, 5, 5, 5);

        $f = (clone $e)->applyLocalEvent();
        $y = (clone $x)->applyLocalEvent();

        self::assertClock($f, 6, 5, 5);
        self::assertClock($y, 2, 4, 6);

        $g = clone $f;
        $gBis = clone $f;

        $z = clone $y;
        $zBis = clone $y;

        // Test the communication in both way
        $this->clocksSyncCommunication($g, $z);
        $this->clocksSyncCommunication($zBis, $gBis);

        self::assertClock($g, 7, 5, 7);
        self::assertClock($gBis, 7, 5, 7);

        self::assertClock($z, 7, 5, 7);
        self::assertClock($zBis, 7, 5, 7);

        // ASSERT HAPPEN BEFORE ON SAME PROCESS
        self::assertHappenBefore($a, $b); // a -> b
        self::assertHappenBefore($b, $c); // b -> c
        self::assertHappenBefore($c, $d); // c -> d
        self::assertHappenBefore($d, $e); // d -> e
        self::assertHappenBefore($e, $f); // e -> f
        self::assertHappenBefore($f, $g); // f -> g

        self::assertHappenBefore($l, $m); // l -> m
        self::assertHappenBefore($m, $n); // m -> n
        self::assertHappenBefore($n, $o); // n -> o
        self::assertHappenBefore($o, $p); // o -> p

        self::assertHappenBefore($t, $u); // t -> u
        self::assertHappenBefore($u, $v); // u -> v
        self::assertHappenBefore($v, $w); // v -> w
        self::assertHappenBefore($w, $x); // w -> x
        self::assertHappenBefore($x, $y); // x -> y
        self::assertHappenBefore($y, $z); // y -> z

        // ASSERT IDENTICAL
        self::assertIdentical($a, $a); // a == a
        self::assertIdentical($b, $b); // b == b
        self::assertIdentical($l, $l); // l == l
        self::assertIdentical($b, $l); // b == l
        self::assertIdentical($n, $w); // n == w
        self::assertIdentical($o, $x); // o == x
        self::assertIdentical($e, $p); // e == p
        self::assertIdentical($g, $z); // g == z

        // Tep[p] > Tfq[p] && Tfq[q] > Tep[q]
        // ||
        // Tep[q] >= Tfq[q] && Tfq[p] >= Tep[p]
        // ASSERT HAPPEN BEFORE ON DIFFERENT PROCESS
        self::assertHappenBefore($a, $l); // a -> l
        self::assertHappenBefore($a, $m); // a -> m
        self::assertHappenBefore($a, $n); // a -> n
        self::assertHappenBefore($a, $o); // a -> o
        self::assertHappenBefore($a, $p); // a -> p
        self::assertHappenBefore($a, $w); // a -> q
        self::assertHappenBefore($a, $x); // a -> x
        self::assertHappenBefore($a, $y); // a -> y
        self::assertHappenBefore($a, $z); // a -> z

        self::assertHappenBefore($b, $m); // b -> m
        self::assertHappenBefore($b, $n); // b -> n
        self::assertHappenBefore($b, $o); // b -> o
        self::assertHappenBefore($b, $p); // b -> p
        self::assertHappenBefore($b, $w); // b -> q
        self::assertHappenBefore($b, $x); // b -> x
        self::assertHappenBefore($b, $y); // b -> y
        self::assertHappenBefore($b, $z); // b -> z

        self::assertHappenBefore($c, $p); // c -> p
        self::assertHappenBefore($c, $z); // c -> z

        self::assertHappenBefore($d, $p); // d -> p
        self::assertHappenBefore($d, $z); // d -> z

        self::assertHappenBefore($e, $z); // e -> z

        self::assertHappenBefore($f, $z); // f -> z

        self::assertHappenBefore($l, $e); // l -> e
        self::assertHappenBefore($l, $f); // l -> f
        self::assertHappenBefore($l, $g); // l -> g
        self::assertHappenBefore($l, $w); // l -> w
        self::assertHappenBefore($l, $x); // l -> x
        self::assertHappenBefore($l, $y); // l -> y
        self::assertHappenBefore($l, $z); // l -> z

        self::assertHappenBefore($m, $e); // m -> e
        self::assertHappenBefore($m, $f); // m -> f
        self::assertHappenBefore($m, $g); // m -> g
        self::assertHappenBefore($m, $w); // m -> w
        self::assertHappenBefore($m, $x); // m -> x
        self::assertHappenBefore($m, $y); // m -> y
        self::assertHappenBefore($m, $z); // m -> z

        self::assertHappenBefore($n, $e); // n -> e
        self::assertHappenBefore($n, $f); // n -> f
        self::assertHappenBefore($n, $g); // n -> g
        self::assertHappenBefore($n, $x); // n -> x
        self::assertHappenBefore($n, $y); // n -> y
        self::assertHappenBefore($n, $z); // n -> z

        self::assertHappenBefore($o, $e); // o -> e
        self::assertHappenBefore($o, $f); // o -> f
        self::assertHappenBefore($o, $g); // o -> g
        self::assertHappenBefore($o, $y); // o -> y
        self::assertHappenBefore($o, $z); // o -> z

        self::assertHappenBefore($p, $f); // p -> f
        self::assertHappenBefore($p, $g); // p -> g
        self::assertHappenBefore($p, $z); // p -> z

        self::assertHappenBefore($t, $n); // t -> n
        self::assertHappenBefore($t, $o); // t -> o
        self::assertHappenBefore($t, $p); // t -> p
        self::assertHappenBefore($t, $e); // t -> e
        self::assertHappenBefore($t, $f); // t -> f
        self::assertHappenBefore($t, $g); // t -> g

        self::assertHappenBefore($u, $n); // u -> n
        self::assertHappenBefore($u, $o); // u -> o
        self::assertHappenBefore($u, $p); // u -> p
        self::assertHappenBefore($u, $e); // u -> e
        self::assertHappenBefore($u, $f); // u -> f
        self::assertHappenBefore($u, $g); // u -> g

        self::assertHappenBefore($v, $n); // v -> n
        self::assertHappenBefore($v, $o); // v -> o
        self::assertHappenBefore($v, $p); // v -> p
        self::assertHappenBefore($v, $e); // v -> e
        self::assertHappenBefore($v, $f); // v -> f
        self::assertHappenBefore($v, $g); // v -> g

        self::assertHappenBefore($w, $o); // w -> o
        self::assertHappenBefore($w, $p); // w -> p
        self::assertHappenBefore($w, $e); // w -> e
        self::assertHappenBefore($w, $f); // w -> f
        self::assertHappenBefore($w, $g); // w -> g

        self::assertHappenBefore($x, $p); // w -> p
        self::assertHappenBefore($x, $e); // w -> e
        self::assertHappenBefore($x, $f); // w -> f
        self::assertHappenBefore($x, $g); // w -> g

        self::assertHappenBefore($y, $g); // y -> g

        // Tep[p] > Tfq[p] && Tfq[q] > Tep[q]
        // ||
        // Tep[q] >= Tfq[q] && Tfq[p] >= Tep[p]

        // ASSERT CONCURRENT

        self::assertAreConcurrent($a, $t); // a <-> t
        self::assertAreConcurrent($a, $u); // a <-> u
        self::assertAreConcurrent($a, $v); // a <-> v

        self::assertAreConcurrent($b, $t); // b <-> t
        self::assertAreConcurrent($b, $u); // b <-> u
        self::assertAreConcurrent($b, $v); // b <-> v

        self::assertAreConcurrent($c, $t); // c <-> t
        self::assertAreConcurrent($c, $u); // c <-> u
        self::assertAreConcurrent($c, $v); // c <-> v
        self::assertAreConcurrent($c, $m); // c <-> m
        self::assertAreConcurrent($c, $n); // c <-> n
        self::assertAreConcurrent($c, $o); // c <-> o
        self::assertAreConcurrent($c, $w); // c <-> w
        self::assertAreConcurrent($c, $x); // c <-> x
        self::assertAreConcurrent($c, $y); // c <-> y

        self::assertAreConcurrent($d, $t); // d <-> t
        self::assertAreConcurrent($d, $u); // d <-> u
        self::assertAreConcurrent($d, $v); // d <-> v
        self::assertAreConcurrent($d, $m); // d <-> m
        self::assertAreConcurrent($d, $n); // d <-> n
        self::assertAreConcurrent($d, $o); // d <-> o
        self::assertAreConcurrent($d, $w); // d <-> w
        self::assertAreConcurrent($d, $x); // d <-> x
        self::assertAreConcurrent($d, $y); // d <-> y

        self::assertAreConcurrent($e, $y); // e <-> y

        self::assertAreConcurrent($l, $t); // l <-> t
        self::assertAreConcurrent($l, $u); // l <-> u
        self::assertAreConcurrent($l, $v); // l <-> v

        self::assertAreConcurrent($m, $t); // m <-> t
        self::assertAreConcurrent($m, $u); // m <-> u
        self::assertAreConcurrent($m, $v); // m <-> v

        self::assertAreConcurrent($p, $y); // p <-> y
    }

    private static function assertClock(SyncVectorClock $clock, int $process1, int $process2, int $process3): void
    {
        self::assertEquals($process1, $clock->getTimestamps()[self::PROCESS_1]->getValue());
        self::assertEquals($process2, $clock->getTimestamps()[self::PROCESS_2]->getValue());
        self::assertEquals($process3, $clock->getTimestamps()[self::PROCESS_3]->getValue());
        self::assertIsIdle($clock);
    }

    private static function assertHappenBefore(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // clock1 -> clock2

        self::assertTrue($clock1->happenBefore($clock2));
        self::assertFalse($clock1->happenAfter($clock2));
        self::assertEquals(ClockOrder::HAPPEN_BEFORE, $clock1->compare($clock2));

        self::assertFalse($clock2->happenBefore($clock1));
        self::assertTrue($clock2->happenAfter($clock1));
        self::assertEquals(ClockOrder::HAPPEN_AFTER, $clock2->compare($clock1));
    }

    private static function assertIdentical(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // clock1 == clock2

        self::assertTrue($clock1->isIdenticalTo($clock2));
        self::assertTrue($clock2->isIdenticalTo($clock1));
        self::assertEquals(ClockOrder::IDENTICAL, $clock1->compare($clock2));
        self::assertEquals(ClockOrder::IDENTICAL, $clock2->compare($clock1));
    }

    private static function assertAreConcurrent(SyncVectorClock $clock1, SyncVectorClock $clock2): void
    {
        // clock1 <-> clock2

        self::assertTrue($clock1->isConcurrentWith($clock2));
        self::assertTrue($clock2->isConcurrentWith($clock1));
        self::assertEquals(ClockOrder::CONCURRENT, $clock1->compare($clock2));
        self::assertEquals(ClockOrder::CONCURRENT, $clock2->compare($clock1));
    }

    private static function clocksSyncCommunication(SyncVectorClock $clockSender, SyncVectorClock $clockReceiver): void
    {
        self::assertIsIdle($clockSender);
        self::assertIsIdle($clockReceiver);

        $clockSender->applySendEvent($clockReceiver->getNode());
        self::assertIsCommunicating($clockSender, $clockReceiver->getNode());
        self::assertIsIdle($clockReceiver);

        $clockReceiver->applyReceiveEvent($clockSender);
        self::assertIsCommunicating($clockSender, $clockReceiver->getNode());
        self::assertIsIdle($clockReceiver);

        $clockSender->applyReceiveEvent($clockReceiver);

        self::assertIsIdle($clockSender);
        self::assertIsIdle($clockReceiver);
    }
}
