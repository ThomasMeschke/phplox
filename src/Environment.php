<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use thomas\phplox\src\exceptions\RuntimeErrorException;

class Environment
{
    /** @var array<string, scalar|null> $values */
    private array $values = [];

    /**
     * @param scalar|null $value
     */
    public function define(string $name, mixed $value) : void
    {
        $this->values[$name] = $value;
    }

    /**
     * @param scalar|null $value;
     */
    public function assign(Token $name, mixed $value) : void
    {
        if (array_key_exists($name->lexeme, $this->values))
        {
            $this->values[$name->lexeme] = $value;
            return;
        }

        throw new RuntimeErrorException($name, "Undefined variable '{$name->lexeme}'.");
    }

    /**
     * @return scalar|null
     */
    public function get(Token $name) : mixed
    {
        if (array_key_exists($name->lexeme, $this->values))
        {
            return $this->values[$name->lexeme];
        }

        throw new RuntimeErrorException($name, "Undefined variable '{$name->lexeme}'.");
    }
}