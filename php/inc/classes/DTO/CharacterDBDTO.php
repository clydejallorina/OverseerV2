<?php

namespace Overseer\DTO;

/**
 * DTO for the Character table in the DB
 * 
 * @api
 */
final class CharacterDBDTO {
    public function __construct(
        public string $name,
        public string $symbol,
        public int $owner,
        public int $server,
        public int $client,
        public string $land1,
        public string $land2,
        public string $consort,
        public string $class,
        public string $aspect,
        public string $echeladder,
        public string $godtier,
        public string $aspectpatterns,
        public string $fraymotifs,
        public string $colour,
        public string $dreamer,
        public string $dreamingstatus,
        public string $wakefatigue,
        public string $dreamfatigue,
        public string $fatiguetimer,
        public string $exploration,
        public string $dungeon,
        public string $dungeoncoords,
        public string $olddungeoncoords,
        public string $proto_preentry,
        public string $proto_obj1,
        public string $proto_obj2,
        public string $proto_effects,
        public string $sprite,
        public string $house_build,
        public string $gatescleared,
        public string $boondollars,
        public string $hascomputer,
        public string $newmessage,
        public string $captchalogues,
        public string $encounters,
        public string $encountersspent,
        public string $lasttick,
        public string $inventory,
        public string $metadata,
        public string $modus,
        public bool $inmedium,
        public bool $firstaspectuse,
        public bool $denizendown,
        public bool $down,
        public bool $dreamdown,
        public array $grist_type,
        public array $abilities,
        public array $strifedeck,
    ) {}
}
