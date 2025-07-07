<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

use thomas\phplox\src\Token;

class FunctionStatement extends Statement
{
    public Token $name;
    /** @var array<Token> $parameters */
    public array $parameters;
    /** @var array<Statement> $body */
    public array $body;

    /**
     * @param array<Token> $parameters
     * @param array<Statement> $body
     */
    public function __construct(Token $name, array $parameters, array $body)
    {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->body = $body;
    }

    /**
     * @template T
     * @param IStatementVisitor<T> $visitor
     * @return T
     */
    public function accept(IStatementVisitor $visitor) : mixed
    {
        return $visitor->visitFunctionStatement($this);
    }
}
