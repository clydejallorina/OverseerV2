<?php

namespace Overseer\Caster;

use CuyZ\Valinor\MapperBuilder;
use Exception;
use ReflectionFunction;

final class Caster {
    /**
     * Converts an associative array into an object (given that the constructor is set appropriately)
     * !!! Automatically converts snake_cased keys to camelCase !!!
     * 
     * @template T
     * @param array<array-key, mixed> $input
     * @param class-string<T> $classString
     * @param list<callable> $customFieldConverters Expects callables with ONLY ONE parameter whose name is an expected camelCased key from the input
     * @return T
     * 
     * @throws Exception
     */
    public static function arrayToObject(
        array $input,
        string $classString,
        array $customFieldConverters = [],
    ): object {
        $builder = new MapperBuilder();
        // Documentation: https://valinor.cuyz.io/latest/usage/type-strictness-and-flexibility/
        $builder = $builder
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
            );
        
        // Handle custom field constructors
        $builder = $builder->registerConverter(
            function (array $values, callable $next) use ($customFieldConverters): object {
                $customFields = [];
                foreach ($customFieldConverters as $fieldConverter) {
                    $reflection = new ReflectionFunction($fieldConverter);
                    if ($reflection->getNumberOfParameters() !== 1) {
                        throw new Exception('Invalid amount of parameters passed to Caster');
                    }
                    $property = $reflection->getParameters()[0]->name;
                    $customFields[$property] = $fieldConverter;
                }

                $fieldConverted = array_combine(
                    keys: array_keys($values),
                    values: array_map(
                        function (string $key, mixed $value) use ($customFields): mixed {
                            if (array_key_exists($key, $customFields)) {
                                return $customFields[$key]($value);
                            }
                            return $value;
                        },
                        array_keys($values),
                        array_values($values),
                    ),
                );

                return $next($fieldConverted);
            }
        );

        return $builder->mapper()
            ->map(
                signature: $classString,
                source: $input,
            );
    }
}
