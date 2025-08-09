<?php

namespace Overseer\Test;

use Overseer\Atheneum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Atheneum::class)]
final class AtheneumTest extends TestCase {
    public function testEmptyAtheneumParsing(): void {
        $atheneum = new Atheneum('');

        $this->assertEmpty($atheneum->items);
    }
}
