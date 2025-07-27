<?php

namespace Overseer\Test\Caster;

use DateTimeImmutable;
use Exception;
use Overseer\Caster\Caster;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Caster::class)]
final class CasterTest extends TestCase {
    public function testBasicCastFromArray(): void {
        $testArray = [
            'integer' => 1,
            'string' => 'str',
            'boolean' => true,
        ];

        $testDTO = new TestDTO(
            integer: 1,
            string: 'str',
            boolean: true,
        );

        $castResult = Caster::arrayToObject(
            input: $testArray,
            classString: TestDTO::class,
        );

        $this->assertEquals($testDTO, $castResult);
    }

    public function testSnakeCaseKeysCast(): void {
        $testArray = [
            'address_line_1' => 'Address Line 1',
            'address_line_2' => 'Address Line 2',
            'city' => 'City',
        ];

        $testDTO = new LocationDTO(
            addressLine1: 'Address Line 1',
            addressLine2: 'Address Line 2',
            city: 'City',
        );

        $castResult = Caster::arrayToObject(
            input: $testArray,
            classString: LocationDTO::class,
        );

        $this->assertEquals($testDTO, $castResult);
    }

    public function testCustomFieldConstructor(): void {
        $testArray = [
            'address_line_1' => 'Address Line 1',
            'address_line_2' => 'Address Line 2',
            'city' => 'City',
            'timeAndDate' => '2009-4-13'
        ];

        $testDTO = new LocationDTO(
            addressLine1: 'Address Line 1',
            addressLine2: 'Address Line 2',
            city: 'City',
            timeAndDate: new DateTimeImmutable('2009-4-13'),
        );

        $castResult = Caster::arrayToObject(
            input: $testArray,
            classString: LocationDTO::class,
            customFieldConverters: [
                fn (string $timeAndDate): DateTimeImmutable => new DateTimeImmutable($timeAndDate),
            ],
        );

        $this->assertEquals($testDTO, $castResult);
    }

    public function testInvalidCast(): void {
        $this->expectException(Exception::class);

        $testArray = [
            'integer' => 'invalid',
            'string' => 'str',
            'boolean' => true,
        ];

        Caster::arrayToObject(
            input: $testArray,
            classString: LocationDTO::class,
        );
    }
}
