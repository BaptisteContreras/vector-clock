<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\AsyncVectorClock;
use Dynamophp\VectorClock\Exception\InvalidNodeNameException;
use Dynamophp\VectorClock\Exception\NumericNodeNameException;
use Dynamophp\VectorClock\LogicalTimestamp;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class AsyncVectorClockNodeAccessorsTest extends TestCase
{
    private const DEFAULT_NODE = 'NODE-TEST';

    #[TestWith([' '])]
    #[TestWith(['a'])]
    #[TestWith(['A'])]
    #[TestWith(['A'])]
    #[TestWith(['null'])]
    #[TestWith(['%00'])]
    #[TestWith(['true'])]
    #[TestWith(['false'])]
    #[TestWith(['thisisatest'])]
    #[TestWith(['this-is @&é~"#\'{([-|è`_\\ç^à)]=}+$£¤*%µtest'])]
    public function testAddNode(string $nodeToAdd): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE);

        self::assertTrue($vectorClock->addNode($nodeToAdd));

        foreach ([self::DEFAULT_NODE, $nodeToAdd] as $expectedNode) {
            self::assertArrayHasKey($expectedNode, $vectorClock->getTimestamps());
        }
    }

    #[TestWith(['-0'])]
    #[TestWith(['0'])]
    #[TestWith(['-1'])]
    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith(['-2'])]
    #[TestWith(['99'])]
    #[TestWith(['-99'])]
    #[TestWith(['-0.'])]
    #[TestWith(['0.'])]
    #[TestWith(['-1.'])]
    #[TestWith(['1.'])]
    #[TestWith(['2.'])]
    #[TestWith(['-2.'])]
    #[TestWith(['99.'])]
    #[TestWith(['-99.'])]
    #[TestWith(['-0.0'])]
    #[TestWith(['0.0'])]
    #[TestWith(['-1.0'])]
    #[TestWith(['1.0'])]
    #[TestWith(['2.0'])]
    #[TestWith(['-2.0'])]
    #[TestWith(['99.0'])]
    #[TestWith(['-99.0'])]
    #[TestWith(['-0.'])]
    #[TestWith(['0.1'])]
    #[TestWith(['-1.1'])]
    #[TestWith(['1.1'])]
    #[TestWith(['2.1'])]
    #[TestWith(['-2.1'])]
    #[TestWith(['99.1'])]
    #[TestWith(['-99.1'])]
    #[TestWith([-0])]
    #[TestWith([0])]
    #[TestWith([-1])]
    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith([-2])]
    #[TestWith([99])]
    #[TestWith([-99])]
    #[TestWith([-0.])]
    #[TestWith([0.])]
    #[TestWith([-1.])]
    #[TestWith([1.])]
    #[TestWith([2.])]
    #[TestWith([-2.])]
    #[TestWith([99.])]
    #[TestWith([-99.])]
    #[TestWith([-0.0])]
    #[TestWith([0.0])]
    #[TestWith([-1.0])]
    #[TestWith([1.0])]
    #[TestWith([2.0])]
    #[TestWith([-2.0])]
    #[TestWith([99.0])]
    #[TestWith([-99.0])]
    #[TestWith([-0.])]
    #[TestWith([0.1])]
    #[TestWith([-1.1])]
    #[TestWith([1.1])]
    #[TestWith([2.1])]
    #[TestWith([-2.1])]
    #[TestWith([99.1])]
    #[TestWith([-99.1])]
    #[TestWith([true])]
    public function testAddNodeFailsIfNodeIsNotValid(string|int|float|bool $nodeToAdd): void
    {
        self::expectException(NumericNodeNameException::class);
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE);
        $vectorClock->addNode($nodeToAdd);
    }

    #[TestWith([''])]
    #[TestWith([false])]
    public function testAddNodeFailsIfNodeNameIsForbiddenValue(string|bool $nodeToAdd): void
    {
        self::expectException(InvalidNodeNameException::class);
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE);
        $vectorClock->addNode($nodeToAdd);
    }

    #[TestWith([self::DEFAULT_NODE])]
    #[TestWith(['NODE-TOTO'])]
    public function testAddNodeReturnsFalseIfNodeIsAlreadyPresent(string $nodeToAdd): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE, ['NODE-TOTO' => LogicalTimestamp::init()]);

        self::assertFalse($vectorClock->addNode($nodeToAdd));
    }

    #[TestWith([' '])]
    #[TestWith(['a'])]
    #[TestWith(['A'])]
    #[TestWith(['A'])]
    #[TestWith(['null'])]
    #[TestWith(['%00'])]
    #[TestWith(['true'])]
    #[TestWith(['false'])]
    #[TestWith(['thisisatest'])]
    #[TestWith(['this-is @&é~"#\'{([-|è`_\\ç^à)]=}+$£¤*%µtest'])]
    public function testSeveralSameAdd(string $nodeToAdd): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE);

        self::assertTrue($vectorClock->addNode($nodeToAdd));
        self::assertFalse($vectorClock->addNode($nodeToAdd));

        foreach ([self::DEFAULT_NODE, $nodeToAdd] as $expectedNode) {
            self::assertArrayHasKey($expectedNode, $vectorClock->getTimestamps());
        }
    }

    #[TestWith([' '])]
    #[TestWith(['a'])]
    #[TestWith(['A'])]
    #[TestWith(['A'])]
    #[TestWith(['null'])]
    #[TestWith(['%00'])]
    #[TestWith(['true'])]
    #[TestWith(['false'])]
    #[TestWith(['thisisatest'])]
    #[TestWith(['this-is @&é~"#\'{([-|è`_\\ç^à)]=}+$£¤*%µtest'])]
    public function testSeveralSameAddWithInitialContext(string $nodeToAdd): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE, ['NODE-TOTO' => LogicalTimestamp::init()]);

        self::assertTrue($vectorClock->addNode($nodeToAdd));
        self::assertFalse($vectorClock->addNode($nodeToAdd));

        foreach ([self::DEFAULT_NODE, 'NODE-TOTO', $nodeToAdd] as $expectedNode) {
            self::assertArrayHasKey($expectedNode, $vectorClock->getTimestamps());
        }
    }

    #[TestWith([' '])]
    #[TestWith(['a'])]
    #[TestWith(['A'])]
    #[TestWith(['A'])]
    #[TestWith(['null'])]
    #[TestWith(['%00'])]
    #[TestWith(['true'])]
    #[TestWith(['false'])]
    #[TestWith(['thisisatest'])]
    #[TestWith(['this-is @&é~"#\'{([-|è`_\\ç^à)]=}+$£¤*%µtest'])]
    public function testRemoveNode(string $nodeToRemove): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE, [$nodeToRemove => LogicalTimestamp::init()]);

        self::assertTrue($vectorClock->removeNode($nodeToRemove));
        self::assertArrayHasKey(self::DEFAULT_NODE, $vectorClock->getTimestamps());
    }

    #[TestWith([' '])]
    #[TestWith(['a'])]
    #[TestWith(['A'])]
    #[TestWith(['A'])]
    #[TestWith(['null'])]
    #[TestWith(['%00'])]
    #[TestWith(['true'])]
    #[TestWith(['false'])]
    #[TestWith(['thisisatest'])]
    #[TestWith(['this-is @&é~"#\'{([-|è`_\\ç^à)]=}+$£¤*%µtest'])]
    public function testRemoveNonExistingNodeReturnsFalse(string $nodeToRemove): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE);

        self::assertFalse($vectorClock->removeNode($nodeToRemove));
        self::assertArrayHasKey(self::DEFAULT_NODE, $vectorClock->getTimestamps());
    }

    #[TestWith([' '])]
    #[TestWith(['a'])]
    #[TestWith(['A'])]
    #[TestWith(['A'])]
    #[TestWith(['null'])]
    #[TestWith(['%00'])]
    #[TestWith(['true'])]
    #[TestWith(['false'])]
    #[TestWith(['thisisatest'])]
    #[TestWith(['this-is @&é~"#\'{([-|è`_\\ç^à)]=}+$£¤*%µtest'])]
    public function testRemoveNonExistingNodeWithInitialContextReturnsFalse(string $nodeToRemove): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE, ['NODE-TOTO' => LogicalTimestamp::init()]);

        self::assertFalse($vectorClock->removeNode($nodeToRemove));
        foreach ([self::DEFAULT_NODE, 'NODE-TOTO'] as $expectedNode) {
            self::assertArrayHasKey($expectedNode, $vectorClock->getTimestamps());
        }
    }

    public function testCannotRemoveMainNode(): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE);

        self::assertFalse($vectorClock->removeNode(self::DEFAULT_NODE));
        self::assertArrayHasKey(self::DEFAULT_NODE, $vectorClock->getTimestamps());
    }

    public function testCannotRemoveMainNodeWithInitialContext(): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE, ['NODE-TOTO' => LogicalTimestamp::init()]);

        self::assertFalse($vectorClock->removeNode(self::DEFAULT_NODE));
        foreach ([self::DEFAULT_NODE, 'NODE-TOTO'] as $expectedNode) {
            self::assertArrayHasKey($expectedNode, $vectorClock->getTimestamps());
        }
    }

    #[TestWith(['-0'])]
    #[TestWith(['0'])]
    #[TestWith(['-1'])]
    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith(['-2'])]
    #[TestWith(['99'])]
    #[TestWith(['-99'])]
    #[TestWith(['-0.'])]
    #[TestWith(['0.'])]
    #[TestWith(['-1.'])]
    #[TestWith(['1.'])]
    #[TestWith(['2.'])]
    #[TestWith(['-2.'])]
    #[TestWith(['99.'])]
    #[TestWith(['-99.'])]
    #[TestWith(['-0.0'])]
    #[TestWith(['0.0'])]
    #[TestWith(['-1.0'])]
    #[TestWith(['1.0'])]
    #[TestWith(['2.0'])]
    #[TestWith(['-2.0'])]
    #[TestWith(['99.0'])]
    #[TestWith(['-99.0'])]
    #[TestWith(['-0.'])]
    #[TestWith(['0.1'])]
    #[TestWith(['-1.1'])]
    #[TestWith(['1.1'])]
    #[TestWith(['2.1'])]
    #[TestWith(['-2.1'])]
    #[TestWith(['99.1'])]
    #[TestWith(['-99.1'])]
    #[TestWith([-0])]
    #[TestWith([0])]
    #[TestWith([-1])]
    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith([-2])]
    #[TestWith([99])]
    #[TestWith([-99])]
    #[TestWith([-0.])]
    #[TestWith([0.])]
    #[TestWith([-1.])]
    #[TestWith([1.])]
    #[TestWith([2.])]
    #[TestWith([-2.])]
    #[TestWith([99.])]
    #[TestWith([-99.])]
    #[TestWith([-0.0])]
    #[TestWith([0.0])]
    #[TestWith([-1.0])]
    #[TestWith([1.0])]
    #[TestWith([2.0])]
    #[TestWith([-2.0])]
    #[TestWith([99.0])]
    #[TestWith([-99.0])]
    #[TestWith([-0.])]
    #[TestWith([0.1])]
    #[TestWith([-1.1])]
    #[TestWith([1.1])]
    #[TestWith([2.1])]
    #[TestWith([-2.1])]
    #[TestWith([99.1])]
    #[TestWith([-99.1])]
    #[TestWith([true])]
    #[TestWith([false])]
    #[TestWith([''])]
    public function testCannotRemoveInvalidNode(string|int|float|bool $nodeToRemove): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE);

        self::assertFalse($vectorClock->removeNode($nodeToRemove));
        self::assertArrayHasKey(self::DEFAULT_NODE, $vectorClock->getTimestamps());
    }

    #[TestWith(['-0'])]
    #[TestWith(['0'])]
    #[TestWith(['-1'])]
    #[TestWith(['1'])]
    #[TestWith(['2'])]
    #[TestWith(['-2'])]
    #[TestWith(['99'])]
    #[TestWith(['-99'])]
    #[TestWith(['-0.'])]
    #[TestWith(['0.'])]
    #[TestWith(['-1.'])]
    #[TestWith(['1.'])]
    #[TestWith(['2.'])]
    #[TestWith(['-2.'])]
    #[TestWith(['99.'])]
    #[TestWith(['-99.'])]
    #[TestWith(['-0.0'])]
    #[TestWith(['0.0'])]
    #[TestWith(['-1.0'])]
    #[TestWith(['1.0'])]
    #[TestWith(['2.0'])]
    #[TestWith(['-2.0'])]
    #[TestWith(['99.0'])]
    #[TestWith(['-99.0'])]
    #[TestWith(['-0.'])]
    #[TestWith(['0.1'])]
    #[TestWith(['-1.1'])]
    #[TestWith(['1.1'])]
    #[TestWith(['2.1'])]
    #[TestWith(['-2.1'])]
    #[TestWith(['99.1'])]
    #[TestWith(['-99.1'])]
    #[TestWith([-0])]
    #[TestWith([0])]
    #[TestWith([-1])]
    #[TestWith([1])]
    #[TestWith([2])]
    #[TestWith([-2])]
    #[TestWith([99])]
    #[TestWith([-99])]
    #[TestWith([-0.])]
    #[TestWith([0.])]
    #[TestWith([-1.])]
    #[TestWith([1.])]
    #[TestWith([2.])]
    #[TestWith([-2.])]
    #[TestWith([99.])]
    #[TestWith([-99.])]
    #[TestWith([-0.0])]
    #[TestWith([0.0])]
    #[TestWith([-1.0])]
    #[TestWith([1.0])]
    #[TestWith([2.0])]
    #[TestWith([-2.0])]
    #[TestWith([99.0])]
    #[TestWith([-99.0])]
    #[TestWith([-0.])]
    #[TestWith([0.1])]
    #[TestWith([-1.1])]
    #[TestWith([1.1])]
    #[TestWith([2.1])]
    #[TestWith([-2.1])]
    #[TestWith([99.1])]
    #[TestWith([-99.1])]
    #[TestWith([true])]
    #[TestWith([false])]
    #[TestWith([''])]
    public function testCannotRemoveInvalidNodeWithInitialContext(string|int|float|bool $nodeToRemove): void
    {
        $vectorClock = new AsyncVectorClock(self::DEFAULT_NODE, ['NODE-TOTO' => LogicalTimestamp::init()]);

        self::assertFalse($vectorClock->removeNode($nodeToRemove));
        foreach ([self::DEFAULT_NODE, 'NODE-TOTO'] as $expectedNode) {
            self::assertArrayHasKey($expectedNode, $vectorClock->getTimestamps());
        }
    }
}
