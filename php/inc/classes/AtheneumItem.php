<?php

namespace Overseer;

use Exception;
use Overseer\Enum\AtheneumObtainedStatus;
use Overseer\Enum\Operator;

final class AtheneumItem {
    public int $itemId;
    public AtheneumObtainedStatus $obtained;
    public string $component1;
    public string $component2;
    public Operator $operator;

    public function __construct(string $itemString = '') {
        // Is this okay to set it to this by default?
        $this->obtained = AtheneumObtainedStatus::VALID_CODE_KNOWN;

        if ($itemString !== '') {
            $parsed = $this->deserialize($itemString);
            $this->itemId = $parsed['itemId'];
            $this->obtained = AtheneumObtainedStatus::from($parsed['obtained']);
            $this->component1 = $parsed['component1'];
            $this->component2 = $parsed['component2'];
            $this->operator = Operator::from($parsed['operator']);
        }
    }

    /**
     * Recipe Format:
     * 
     * $component1//$component2 -> OR
     * 
     * $component3&&$component4 -> AND
     */
    public function setRecipe(string $component1, string $component2, Operator $operator): self {
        $this->component1 = $component1;
        $this->component2 = $component2;
        $this->operator = $operator;

        return $this;
    }

    public function getRecipe(): string {
        return "{$this->component1}{$this->operator->value}{$this->component2}";
    }

    /**
     * @return array{
     *   component1: string,
     *   component2: string,
     *   operator: string,
     * }
     */
    public function parseRecipe(string $recipe): array {
        $matches = [];
        preg_match(
            pattern: '/([0-9A-Za-z?!]+)(\/\/|&&)([0-9A-Za-z?!]+)/',
            subject: $recipe,
            matches: $matches,
        );

        if (empty($matches) || count($matches) !== 4) {
            throw new Exception("Cannot parse recipe '{$recipe}'");
        }

        return [
            'component1' => $matches[1],
            'component2' => $matches[3],
            'operator' => $matches[2],
        ];
    } 

    /**
     * @return array{
     *   itemId: int,
     *   obtained: int,
     *   component1: string,
     *   component2: string,
     *   operator: string,
     * }
     */
    public function deserialize(string $itemString): array {
        $exploded = explode(':', $itemString);
        if (count($exploded) !== 3) {
            throw new Exception("Cannot deserialize invalid item string '{$itemString}'");
        }
        $recipe = $this->parseRecipe($exploded[2]);

        return [
            'itemId' => (int)$exploded[0],
            'obtained' => (int)$exploded[1],
            'component1' => $recipe['component1'],
            'component2' => $recipe['component2'],
            'operator' => $recipe['operator'],
        ];
    }

    public function serialize(): string {
        $recipe = $this->getRecipe();
        return "{$this->itemId}:{$this->obtained->value}:{$recipe}";
    }
}
