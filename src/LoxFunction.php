<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use thomas\phplox\src\ast\FunctionStatement;
use thomas\phplox\src\exceptions\ReturnException;

class LoxFunction implements LoxCallable
{
    private readonly FunctionStatement $declaration;
    private readonly Environment $closure;

    public function __construct(FunctionStatement $declaration, Environment $closure)
    {
        $this->declaration = $declaration;
        $this->closure = $closure;
    }

    public function arity(): int
    {
        return count($this->declaration->parameters);
    }

    public function call(Interpreter $interpreter, array $arguments): mixed
    {
        $environment = new Environment($this->closure);
        for ($i = 0; $i < count($this->declaration->parameters); $i++)
        {
            $environment->define(
                $this->declaration->parameters[$i]->lexeme,
                $arguments[$i]
            );
        }

        try
        {
            $interpreter->executeBlock($this->declaration->body, $environment);
        }
        catch(ReturnException $return)
        {
            return $return->value;
        }

        return null;
    }

    public function __toString()
    {
        $functionName = $this->declaration->name->lexeme;
        return "<fn {$functionName}>";
    }
}
