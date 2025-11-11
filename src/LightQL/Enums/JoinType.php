<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Enums;

/**
 * SQL Join Types
 */
enum JoinType: string
{
    case INNER = 'INNER';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
    case FULL = 'FULL';
    case CROSS = 'CROSS';
}
