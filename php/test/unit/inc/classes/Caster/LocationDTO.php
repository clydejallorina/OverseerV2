<?php

namespace Overseer\Test\Caster;

use DateTimeImmutable;

/**
 * @api
 */
final class LocationDTO {
    public function __construct(
        public string $addressLine1,
        public string $addressLine2,
        public string $city,
        public ?DateTimeImmutable $timeAndDate = null,
    ) {}
}
