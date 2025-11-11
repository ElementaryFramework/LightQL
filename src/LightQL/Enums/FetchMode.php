<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Enums;

enum FetchMode: int
{
    case LAZY = 0;

    case EAGER = 1;
}
