<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use thomas\phplox\src\exceptions\RuntimeErrorException;

class Environment
{
    public ?Environment $enclosing;

    /** @var array<mixed> $values */
    private array $values = [];

    public function __construct(?Environment $enclosing = null)
    {
        $this->enclosing = $enclosing;
    }

    public function define(string $name, mixed $value) : void
    {
        $this->values[$name] = $value;
    }

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

    public function assignAt(int $distance, Token $name, mixed $value) : void
    {
        $this->ancestor($distance)->values[$name->lexeme] = $value;
    }

    public function getAt(int $distance, string $name) : mixed
    {
        return $this->ancestor($distance)->values[$name];
    }

    public function ancestor(int $distance) : Environment
    {
        $environment = $this;
        for($i = 0; $i < $distance; $i++)
        {
            /** @var Environment $environment */
            $environment = $environment->enclosing;
        }

        return $environment;
    }
}