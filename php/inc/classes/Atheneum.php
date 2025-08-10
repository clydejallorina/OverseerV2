<?php

namespace Overseer;

use ArrayAccess;
use Exception;
use Overseer\AtheneumItem;

use function PHPUnit\Framework\callback;

final class Atheneum implements ArrayAccess {
    /** @var array{int, AtheneumItem} Item array where the key is the item (Captchalogue) ID */
    public array $items;
    
    public function __construct(string $atheneum = '') {
        $this->items = $this->deserialize($atheneum);
    }

    public function count(): int {
        return count($this->items);
    }

    public function serialize(): string {
        if ($this->count() === 0) {
            return '';
        }

        return implode('|', array_map(
            callback: function (AtheneumItem $item): string {return $item->serialize();},
            array: array_values($this->items)
        )) . '|';
    }

    /**
     * @return array{int, AtheneumItem}
     */
    public function deserialize(string $serialized): array {
        $itemArray = [];

        foreach (explode('|', $serialized) as $itemString) {
            try {
                $item = new AtheneumItem($itemString);
            } catch (Exception $e) {
                // TODO: Log this as a warning
                // This item has an invalid string, so let's ignore it.
                continue;
            }

            if (!isset($item->itemId)) {
                // TODO: Log this as a warning
                // Item data is empty, ignore it.
                continue;
            }

            $itemArray[$item->itemId] = $item;
        }

        return $itemArray;
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        if (!is_int($offset)) {
            throw new Exception('Non-integer offset passed');
        }
        if (!$value instanceof AtheneumItem) {
            throw new Exception('Invalid value type passed');
        }
        $this->items[$offset] = $value;
    }
    public function offsetExists(mixed $offset): bool {
        if (is_int($offset)) {
            return array_key_exists($offset, $this->items);
        }
        if ($offset instanceof AtheneumItem) {
            return array_key_exists($offset->itemId, $this->items);
        }
        throw new Exception('Undefined offset passed');
    }
    public function offsetUnset(mixed $offset): void {
        if (is_int($offset)) {
            unset($this->items[$offset]);
            return;
        }
        if ($offset instanceof AtheneumItem) {
            unset($this->items[$offset->itemId]);
            return;
        }
        throw new Exception('Undefined offset passed');
    }
    public function offsetGet(mixed $offset): mixed {
        if (!is_int($offset)) {
            throw new Exception('Non-integer offset passed');
        }
        if (array_key_exists($offset, $this->items)) {
            return $this->items[$offset];
        }
        throw new Exception('Undefined offset passed');
    }
}
