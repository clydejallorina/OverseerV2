<?php

namespace Overseer\Test\Inc\Classes;

use Overseer\Code;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Code::class)]
final class CodeTest extends TestCase {
    /**
     * @return iterable<string, array{
     *   a: string,
     *   b: string,
     *   expectedCode: string,
     *   operation: string,
     *  }>
     */
    public static function operationsDataProvider(): iterable
    {
        yield 'combineOr' => [
            'codeA' => 'ABCDEFGH',
            'codeB' => '12345678',
            'operation' => 'combineOr',
            'expectedCode' => 'BBFDFFNP',
        ];

        yield 'combineAnd' => [
            'codeA' => 'ABCDEFGH',
            'codeB' => '12345678',
            'operation' => 'combineAnd',
            'expectedCode' => '02044600',
        ];

        yield 'combineXor' => [
            'codeA' => 'ABCDEFGH',
            'codeB' => '12345678',
            'operation' => 'combineXor',
            'expectedCode' => 'B9F9B9NP',
        ];
    }

    #[Test]
    #[DataProvider('operationsDataProvider')]
    public function testOperations(
        string $codeA,
        string $codeB,
        string $operation,
        string $expectedCode,
    ): void
    {
        $a = new Code($codeA);
        $b = new Code($codeB);
        
        $this->assertEquals(
            expected: $expectedCode,
            actual: $a->{$operation}($b)->string(),
        );
    }
}
