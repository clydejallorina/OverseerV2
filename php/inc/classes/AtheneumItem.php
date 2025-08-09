<?php

namespace Overseer;

use Overseer\Enum\AtheneumObtainedStatus;
use Overseer\Enum\Operator;

final class AtheneumItem {
    public int $itemId;
    public AtheneumObtainedStatus $obtained;
    /**
     * Recipe Format:
     * 
     * $component1//$component2 -> OR
     * 
     * $component3&&$component4 -> AND
     */
    public string $recipe;

    public function __construct(string $itemString) {
        $parsed = $this->deserialize($itemString);
        $this->itemId = $parsed['itemId'];
        $this->obtained = AtheneumObtainedStatus::from($parsed['obtained']);
        $this->recipe = $parsed['recipe'];
    }

    public function setRecipe(string $component1, string $component2, Operator $operator): self {
        $this->recipe = "{$component1}{$operator->value}{$component2}";

        return $this;
    }

    /**
     * @return array{
     *   itemId: int,
     *   obtained: int,
     *   recipe: string,
     * }
     */
    public function deserialize(string $itemString): array {
        $exploded = explode(':', $itemString);
        return [
            'itemId' => (int)$exploded[0],
            'obtained' => (int)$exploded[1],
            'recipe' => $exploded[2],
        ];
    }

    public function serialize(): string {
        return "{$this->itemId}:{$this->obtained->value}:{$this->recipe}";
    }
}
