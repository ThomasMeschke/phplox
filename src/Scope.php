<?php

declare(strict_types=1);

namespace thomas\phplox\src;

class Scope
{
    /**
     * @var array<string, bool> $declarations
     */
    private array $declarations = [];

    public function declare(string $name) : void
    {
        $this->declarations[$name] = false;
    }

    public function isDeclared(string $name) : bool
    {
        return array_key_exists($name, $this->declarations);
    }

    public function define(string $name) : void
    {
        $this->declarations[$name] = true;
    }

    public function isDefined(string $name) : bool
    {
        return $this->declarations[$name];
    }
}