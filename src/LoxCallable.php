<?php

declare(strict_types=1);

namespace thomas\phplox\src;

interface LoxCallable
{
    function arity() : int;

    /**
     * @param array<mixed> $arguments
     **/
    function call(Interpreter $interpreter, array $arguments) : mixed;
}