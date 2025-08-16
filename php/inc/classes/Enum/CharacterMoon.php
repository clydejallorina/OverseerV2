<?php

namespace Overseer\Enum;

/**
 * Player's moon, as saved in Characters->dreamer
 */
enum CharacterMoon: string
{
    case PROPSIT = 'Prospit';
    case DERSE = 'Derse';
    /** Default in the database, used if your dreamself isn't awakened yet. */
    case UNAWAKENED = 'Unawakened';
}
