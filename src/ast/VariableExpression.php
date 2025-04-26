<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

use thomas\phplox\src\Token;

class VariableExpression extends Expression
{
    public Token $name;

    public function __construct(Token $name)
    {
        $this->name = $name;
    }

    /**
     * @template T
     * @param IExpressionVisitor<T> $visitor
     * @return T
     */
    public function accept(IExpressionVisitor $visitor) : mixed
    {
        return $visitor->visitVariableExpression($this);
    }
}
