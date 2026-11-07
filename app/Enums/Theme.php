<?php

namespace App\Enums;

/**
 * The appearance a user has chosen. System follows the device setting.
 */
enum Theme: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';
}
