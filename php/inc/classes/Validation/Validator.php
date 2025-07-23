<?php

namespace Overseer\Validation;

use CuyZ\Valinor\MapperBuilder;
use Exception;

final class Validator {
    /**
     * Converts an associative array into an object (given that the constructor is set appropriately)
     * Automatically converts snake_cased keys to camelCase
     * 
     * @template T
     * @param array<array-key, mixed> $input
     * @param class-string<T> $classString
     * @param list<callable> $customObjectConstructors
     * @return T
     * 
     * @throws Exception
     */
    public static function arrayToObject(
        array $input,
        string $classString,
        array $customObjectConstructors = [],
    ): object {
        $builder = new MapperBuilder();
        // Documentation: https://valinor.cuyz.io/latest/usage/type-strictness-and-flexibility/
        return $builder
            ->allowScalarValueCasting()
            ->allowUndefinedValues()
            ->allowSuperfluousKeys()
            ->registerConverter(
                function (array $values, callable $next): object {
                    $camelCaseConverted = array_combine(
                        array_map(
                            fn ($key) => lcfirst(str_replace('_', '', ucwords($key, '_'))),
                            array_keys($values),
                        ),
                        $values,
                    );

                    return $next($camelCaseConverted);
                }
            )
            ->mapper()
            ->map(
                signature: $classString,
                source: $input,
            );
    }
}
