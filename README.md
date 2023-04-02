# vector-clock


A PHP implementation of the concept of **Vector Clock** as defined in the paper : **Timestamps in Message-Passing Systems That Preserve the Partial Ordering** (paper/vector-clock-paper.pdf).

This library provides :
- Asynchrone vector clock
- Synchrone vector clock (wip)
- Lamport timestamp (wip)


## Asynchrone vector clock


### Usage

```php
<?php

// We create three clocks, one for each node in our system
$clockNode1 = new AsyncVectorClock('NODE-1');
$clockNode2 = new AsyncVectorClock('NODE-2');
$clockNode3 = new AsyncVectorClock('NODE-3');

// Then, for each clock, we add the others nodes in the current vector 

$clockNode1->addNode('NODE-2');
$clockNode1->addNode('NODE-3');

$clockNode2->addNode('NODE-1');
$clockNode2->addNode('NODE-3');

$clockNode3->addNode('NODE-1');
$clockNode3->addNode('NODE-2');

// All clocks must look like [0, 0, 0]
// After the initialization part, we can play with our clocks

$a = (clone $clockProcess1)->applySendEvent(); // [1, 0, 0]
$l = (clone $clockProcess2)->applyLocalEvent(); // [0, 1, 0]
$v = (clone $clockProcess3)->applyLocalEvent(); // [0, 0, 1]


$b = (clone $a)->applyLocalEvent(); // [2, 0, 0]
$m = (clone $l)->applyReceiveEvent($a); // [2, 2, 0]
$w = (clone $v)->applyLocalEvent(); // [0, 0, 3]

// And one more important thing, we can compare clocks

assertTrue($l->canBeComparedWith($v)); 

assertTrue($a->isIdenticalTo($a)); // a == a
assertTrue($l->isConcurrentWith($v)); // l <-> v

assertTrue($m->happenAfter($a)); // a -> m
assertTrue($a->happenBefore($m)); // a -> m
assertTrue($l->happenBefore($m)); // l -> m

```

In the test case : **AsyncVectorScenarioTest::testPaperFigure3** you can see the full scenario of the paper : 


![img.png](paper/fig3.png)


