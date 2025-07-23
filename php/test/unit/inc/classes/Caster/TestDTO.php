<?php

namespace Overseer\Test\Caster;

/**
 * @api
 */
final class TestDTO {
    public function __construct(
        public int $integer,
        public string $string,
        public bool $boolean,
    ) {}
}
