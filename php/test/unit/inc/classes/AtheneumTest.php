<?php

namespace Overseer\Test;

use Overseer\Atheneum;
use Overseer\AtheneumItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Atheneum::class)]
final class AtheneumTest extends TestCase {
    public function testEmptyAtheneumParsing(): void {
        $atheneum = new Atheneum('');

        $this->assertEmpty($atheneum->items);
    }

    public function testValidAtheneumParsing(): void {
        $stringToParse = '1:1:CsG0g10e//3iY3D120|2:1:3iY3D120&&CsG0g10e|';
        $atheneum = new Atheneum($stringToParse);

        $item1 = new AtheneumItem('1:1:CsG0g10e//3iY3D120');
        $item2 = new AtheneumItem('2:1:3iY3D120&&CsG0g10e');

        $this->assertEquals(2, $atheneum->count());
        $this->assertEquals($item1, $atheneum[$item1->itemId]);
        $this->assertEquals($item2, $atheneum[$item2->itemId]);
        $this->assertEquals($stringToParse, $atheneum->serialize());
    }

    public function testWeirdAtheneumParsing(): void {
        // Writing this test case since we current have atheneums in the database
        // that has the format of '2|2|2|2|' which is really really weird
        $atheneum = new Atheneum('1:1:CsG0g10e//3iY3D120|2|2|2|2|2:1:3iY3D120&&CsG0g10e|2|2|2|');

        $item1 = new AtheneumItem('1:1:CsG0g10e//3iY3D120');
        $item2 = new AtheneumItem('2:1:3iY3D120&&CsG0g10e');

        $this->assertEquals(2, $atheneum->count());
        $this->assertEquals($item1, $atheneum[$item1->itemId]);
        $this->assertEquals($item2, $atheneum[$item2->itemId]);
        $this->assertEquals('1:1:CsG0g10e//3iY3D120|2:1:3iY3D120&&CsG0g10e|', $atheneum->serialize());
    }

    public function testAtheneumUnsetting(): void {
        $stringToParse = '1:1:CsG0g10e//3iY3D120|2:1:3iY3D120&&CsG0g10e|';
        $atheneum = new Atheneum($stringToParse);

        $item1 = new AtheneumItem('1:1:CsG0g10e//3iY3D120');
        $item2 = new AtheneumItem('2:1:3iY3D120&&CsG0g10e');

        unset($atheneum[$item1]);
        unset($atheneum[$item2->itemId]);

        $this->assertEmpty($atheneum->items);
    }
}
