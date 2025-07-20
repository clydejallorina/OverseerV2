<?php

namespace Overseer\Test;

use Overseer\Grist;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Grist::class)]
final class GristTest extends TestCase {
    public function testGristImport(): void {
        $this->markTestSkipped('Unused function');
        // TODO: Finish this test and actually use this?
        // $grist = new Grist();
        // $grist->import('');
    }

    public function testGristImportOld(): void {
        $grist = new Grist();
        $grist->importOld('Build_Grist:20|');

        $dump = $grist->dump();
        $this->assertIsArray($dump);
        $this->assertArrayHasKey('Build_Grist', $dump);
        $this->assertEquals(
            expected: 20,
            actual: $dump['Build_Grist'],
        );

        $grist = new Grist();
        $grist->importOld('Build_Grist:20|Acid:100|');

        $dump = $grist->dump();
        $this->assertIsArray($dump);
        $this->assertArrayHasKey('Build_Grist', $dump);
        $this->assertArrayHasKey('Acid', $dump);
        $this->assertEquals(
            expected: 20,
            actual: $dump['Build_Grist'],
        );
        $this->assertEquals(
            expected: 100,
            actual: $dump['Acid'],
        );
    }

    public function testGristAdd(): void {
        $grist = new Grist();
        $grist->add('Build_Grist', 100);

        $dump = $grist->dump();
        $this->assertIsArray($dump);
        $this->assertArrayHasKey('Build_Grist', $dump);
        $this->assertEquals(
            expected: 100,
            actual: $dump['Build_Grist'],
        );
    }

    /**
     * @return iterable<string, array{
     *   expectedGristLeft: int,
     *   expectedReturn: bool,
     *   gristToRemove: int,
     *   gristType: string,
     *   startingGristAmount: int,
     * }>
     */
    public static function gristRemoveDataProvider(): iterable {
        yield 'remove grist with enough grist remaining' => [
            'gristType' => 'Build_Grist',
            'startingGristAmount' => 100,
            'gristToRemove' => 1,
            'expectedGristLeft' => 99,
            'expectedReturn' => true,
        ];

        yield 'remove grist with no grist remaining' => [
            'gristType' => 'Build_Grist',
            'startingGristAmount' => 100,
            'gristToRemove' => 100,
            'expectedGristLeft' => 0,
            'expectedReturn' => true,
        ];

        yield 'remove too much grist' => [
            'gristType' => 'Build_Grist',
            'startingGristAmount' => 100,
            'gristToRemove' => 300,
            'expectedGristLeft' => 100,
            'expectedReturn' => false,
        ];
    }

    #[DataProvider('gristRemoveDataProvider')]
    public function testGristRemove(
        string $gristType,
        int $startingGristAmount,
        int $gristToRemove,
        int $expectedGristLeft,
        bool $expectedReturn,
    ): void {
        $grist = new Grist();
        $grist->add($gristType, $startingGristAmount);
        $returnValue = $grist->remove($gristType, $gristToRemove);
        $gristLeft = $grist->get($gristType);
        $this->assertEquals(
            expected: $expectedReturn,
            actual: $returnValue,
        );
        $this->assertEquals(
            expected:$expectedGristLeft,
            actual: $gristLeft,
        );
    }

    public function testGristRemoveAll(): void {
        $grist = new Grist();
        $grist->add('Build_Grist', 100);
        $grist->add('Acid', 125);

        $dump = $grist->dump();
        $this->assertIsArray($dump);
        $this->assertArrayHasKey('Build_Grist', $dump);
        $this->assertArrayHasKey('Acid', $dump);
        $this->assertEquals(
            expected: 100,
            actual: $dump['Build_Grist'],
        );
        $this->assertEquals(
            expected: 125,
            actual: $dump['Acid'],
        );

        $returnValue = $grist->removeAll('Build_Grist');
        $this->assertTrue($returnValue);

        $dump = $grist->dump();
        $this->assertIsArray($dump);
        $this->assertArrayNotHasKey('Build_Grist', $dump);
        $this->assertArrayHasKey('Acid', $dump);
        $this->assertEquals(
            expected: 125,
            actual: $dump['Acid'],
        );
    }
}