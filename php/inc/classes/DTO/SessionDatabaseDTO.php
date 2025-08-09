<?php

namespace Overseer\DTO;

final class SessionDatabaseDTO {
    public function __construct(
        public int $id,
        public string $name,
        public string $creator,
        public string $members,
        public string $password,
        public int $battlefieldPower,
        public string $atheneum,
        public int $exchange,
    ) {}
}
