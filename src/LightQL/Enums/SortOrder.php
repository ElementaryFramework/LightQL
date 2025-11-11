<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Enums;

/**
 * SQL Sort Order
 */
enum SortOrder: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}
