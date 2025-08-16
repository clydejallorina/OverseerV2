<?php

namespace Overseer\Test;

use Exception;
use Overseer\AtheneumItem;
use Overseer\Enum\Operator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AtheneumItem::class)]
final class AtheneumItemTest extends TestCase {
    public function testItemParsing(): void {
        // Using AND
        $item = new AtheneumItem();
        $item->itemId = 1234;
        $item->setRecipe(
            component1: '3iY3D120',
            component2: 'CsG0g10e',
            operator: Operator::AND,
        );
        $itemCopy = new AtheneumItem($item->serialize());

        $this->assertEquals($item, $itemCopy);

        // Using OR
        $item = new AtheneumItem();
        $item->itemId = 1234;
        $item->setRecipe(
            component1: 'CsG0g10e',
            component2: '3iY3D120',
            operator: Operator::OR,
        );
        $itemCopy = new AtheneumItem($item->serialize());

        $this->assertEquals($item, $itemCopy);
    }

    public function testInvalidRecipe(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cannot parse recipe 'invalid recipe'");

        $item = new AtheneumItem();
        $item->parseRecipe('invalid recipe');
    }
}
