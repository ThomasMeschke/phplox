<?php

declare(strict_types=1);

namespace thomas\phplox\src\exceptions;

use Exception;

class ReturnException extends Exception
{
    public readonly mixed $value;

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }
}