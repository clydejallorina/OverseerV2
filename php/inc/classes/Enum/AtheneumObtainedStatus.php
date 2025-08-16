<?php

namespace Overseer\Enum;

enum AtheneumObtainedStatus: int
{
    /** Known to be a valid code */
    case VALID_CODE_KNOWN = 0;
    /** Name, Image, and Description known */
    case BASIC_INFO_KNOWN = 1;
    /** All info known (i.e. was obtained or viewed with holo+) */
    case ALL_INFO_KNOWN = 2;
}
