<?php

declare(strict_types=1);

namespace thomas\phplox\src;

enum FunctionType : string
{
    case NONE = "none";
    case FUNCTION = "function";
}