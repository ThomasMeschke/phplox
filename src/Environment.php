<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use thomas\phplox\src\exceptions\RuntimeErrorException;

class Environment
{
    public ?Environment $enclosing;

    /** @var array<string, scalar|null> $values */
    private array $values = [];

    public function __construct(?Environment $enclosing = null)
    {
        $this->enclosing = $enclosing;
    }

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

        if (null !== $this->enclosing)
        {
            $this->enclosing->assign($name, $value);
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

        if (null !== $this->enclosing)
        {
            return $this->enclosing->get($name);
        }

        throw new RuntimeErrorException($name, "Undefined variable '{$name->lexeme}'.");
    }
}