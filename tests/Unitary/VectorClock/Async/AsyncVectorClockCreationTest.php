<?php

namespace Dynamophp\VectorClock\Test\Unitary\VectorClock\Async;

use Dynamophp\VectorClock\AsyncVectorClock;
use Dynamophp\VectorClock\Exception\InvalidNodeNameException;
use Dynamophp\VectorClock\Exception\InvalidVectorClockStateException;
use Dynamophp\VectorClock\Exception\NumericNodeNameException;
use Dynamophp\VectorClock\LogicalTimestamp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class AsyncVectorClockCreationTest extends TestCase
{
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
    public function testWithoutInitialContext(string $node): void
    {
        $clock = new AsyncVectorClock($node);

        self::assertSame($node, $clock->getNode());

        $timestamps = $clock->getTimestamps();
        self::assertCount(1, $timestamps);
        self::assertArrayHasKey($node, $timestamps);
        self::assertSame(0, $timestamps[$node]->getValue());
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
    public function testWithoutInitialContextAndWithNumericNodeThrowException(string|int|float|bool $node): void
    {
        $this->expectException(NumericNodeNameException::class);
        $clock = new AsyncVectorClock($node);
    }

    #[TestWith([''])]
    #[TestWith([false])]
    public function testWithoutInitialContextAndWithForbiddenNodeNameThrowException(string|bool $node): void
    {
        $this->expectException(InvalidNodeNameException::class);
        $clock = new AsyncVectorClock($node);
    }

    #[DataProvider('provideInitialContextWithNumericKey')]
    public function testInitialContextContainsNumericNodeThrowException(string $node, array $initialContext): void
    {
        $this->expectException(NumericNodeNameException::class);
        $clock = new AsyncVectorClock($node, $initialContext);
    }

    #[DataProvider('provideInitialContextWithForbiddenKey')]
    public function testInitialContextContainsForbiddenNodeNameThrowException(string $node, array $initialContext): void
    {
        $this->expectException(InvalidNodeNameException::class);
        $clock = new AsyncVectorClock($node, $initialContext);
    }

    #[DataProvider('provideInitialContextWithBadValue')]
    public function testInitialContextContainsBadValuesThrowException(string $node, array $initialContext): void
    {
        $this->expectException(InvalidVectorClockStateException::class);
        $clock = new AsyncVectorClock($node, $initialContext);
    }

    #[DataProvider('provideInitialContext')]
    public function testInitialContext(string $node, array $initialContext, int $nbExpectedNodes, array $expectedTimestampsValue): void
    {
        $clock = new AsyncVectorClock($node, $initialContext);

        self::assertSame($node, $clock->getNode());

        $timestamps = $clock->getTimestamps();
        self::assertCount($nbExpectedNodes, $timestamps);
        self::assertArrayHasKey($node, $timestamps);

        foreach (array_keys($initialContext) as $initialContextKey) {
            self::assertArrayHasKey($initialContextKey, $timestamps);
            self::assertSame($expectedTimestampsValue[$initialContextKey], $timestamps[$initialContextKey]->getValue());
        }
    }

    public static function provideInitialContextWithNumericKey(): \Generator
    {
        yield ['NODE-TEST', [0 => LogicalTimestamp::init()]];
        yield ['NODE-TEST', [1 => LogicalTimestamp::init()]];
        yield ['NODE-TEST', [-1 => LogicalTimestamp::init()]];
        yield ['NODE-TEST', [0.0 => LogicalTimestamp::init()]];
        yield ['NODE-TEST', [21.12 => LogicalTimestamp::init()]];
        yield ['NODE-TEST', ['99' => LogicalTimestamp::init()]];
        yield ['NODE-TEST', ['-99' => LogicalTimestamp::init()]];
        yield ['NODE-TEST', [true => LogicalTimestamp::init()]];
        yield ['NODE-TEST', [false => LogicalTimestamp::init()]];
        yield ['NODE-TEST', [0 => 'bad_value']];
        yield ['NODE-TEST', [1 => 'bad_value']];
        yield ['NODE-TEST', [-1 => 'bad_value']];
        yield ['NODE-TEST', [0.0 => 'bad_value']];
        yield ['NODE-TEST', [21.12 => 'bad_value']];
        yield ['NODE-TEST', ['99' => 'bad_value']];
        yield ['NODE-TEST', ['-99' => 'bad_value']];
        yield ['NODE-TEST', [true => 'bad_value']];
        yield ['NODE-TEST', [false => 'bad_value']];
    }

    public static function provideInitialContextWithForbiddenKey(): \Generator
    {
        yield ['NODE-TEST', ['' => LogicalTimestamp::init()]];
        yield ['NODE-TEST', ['' => 'bad_value']];
    }

    public static function provideInitialContextWithBadValue(): \Generator
    {
        yield ['NODE-TEST', ['NODE-TOTO' => 1]];
        yield ['NODE-TEST', ['NODE-TOTO' => 1.0]];
        yield ['NODE-TEST', ['NODE-TOTO' => 0]];
        yield ['NODE-TEST', ['NODE-TOTO' => 0.0]];
        yield ['NODE-TEST', ['NODE-TOTO' => 99]];
        yield ['NODE-TEST', ['NODE-TOTO' => 'bad_value']];
        yield ['NODE-TEST', ['NODE-TOTO' => null]];
        yield ['NODE-TEST', ['NODE-TOTO' => new \stdClass()]];
        yield ['NODE-TEST', ['NODE-TOTO' => []]];
        yield ['NODE-TEST', ['NODE-TOTO' => [LogicalTimestamp::init()]]];
        yield ['NODE-TEST', ['NODE-TOTO' => ['nested' => LogicalTimestamp::init()]]];
        yield ['NODE-TEST', ['NODE-TOTO' => true]];
        yield ['NODE-TEST', ['NODE-TOTO' => false]];
        yield ['NODE-TEST', ['NODE-TOTO' => '']];
        yield ['NODE-TEST', ['NODE-TOTO' => ' ']];
        yield ['NODE-TEST', ['NODE-TOTO' => '%00']];
        yield ['NODE-TEST', ['NODE-TOTO' => 'bad_value']];
        yield ['NODE-TEST', ['NODE-TEST' => 1]];
        yield ['NODE-TEST', ['NODE-TEST' => 1.0]];
        yield ['NODE-TEST', ['NODE-TEST' => 0]];
        yield ['NODE-TEST', ['NODE-TEST' => 0.0]];
        yield ['NODE-TEST', ['NODE-TEST' => 99]];
        yield ['NODE-TEST', ['NODE-TEST' => 'bad_value']];
        yield ['NODE-TEST', ['NODE-TEST' => new \stdClass()]];
        yield ['NODE-TEST', ['NODE-TEST' => []]];
        yield ['NODE-TEST', ['NODE-TEST' => [LogicalTimestamp::init()]]];
        yield ['NODE-TEST', ['NODE-TEST' => ['nested' => LogicalTimestamp::init()]]];
        yield ['NODE-TEST', ['NODE-TEST' => true]];
        yield ['NODE-TEST', ['NODE-TEST' => false]];
        yield ['NODE-TEST', ['NODE-TEST' => '']];
        yield ['NODE-TEST', ['NODE-TEST' => ' ']];
        yield ['NODE-TEST', ['NODE-TEST' => '%00']];
        yield ['NODE-TEST', ['NODE-TEST' => 'bad_value']];
    }

    public static function provideInitialContext(): \Generator
    {
        yield 'Passing null as value in the initial context for the same node is valid because and the default node value is used' => ['NODE-TEST', ['NODE-TEST' => null], 1, ['NODE-TEST' => 0]];
        yield ['NODE-TEST', ['NODE-TOTO' => LogicalTimestamp::init()], 2, ['NODE-TEST' => 0, 'NODE-TOTO' => 0]];
        yield ['NODE-TEST', ['NODE-TOTO' => new LogicalTimestamp(1)], 2, ['NODE-TEST' => 0, 'NODE-TOTO' => 1]];
        yield ['NODE-TEST', ['NODE-TEST' => LogicalTimestamp::init(), 'NODE-TOTO' => new LogicalTimestamp(1)], 2, ['NODE-TEST' => 0, 'NODE-TOTO' => 1]];
        yield ['NODE-TEST', ['NODE-TEST' => new LogicalTimestamp(99), 'NODE-TOTO' => new LogicalTimestamp(1)], 2, ['NODE-TEST' => 99, 'NODE-TOTO' => 1]];
        yield ['NODE-TEST', [], 1, ['NODE-TEST' => 0]];
        yield ['NODE-TEST', ['NODE-TEST' => new LogicalTimestamp(99)], 1, ['NODE-TEST' => 99]];
    }
}
