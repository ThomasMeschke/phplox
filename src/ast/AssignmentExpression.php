<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

use thomas\phplox\src\Token;

class AssignmentExpression extends Expression
{
    public Token $name;
    public Expression $value;

    public function __construct(Token $name, Expression $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @template T
     * @param IExpressionVisitor<T> $visitor
     * @return T
     */
    public function accept(IExpressionVisitor $visitor) : mixed
    {
        return $visitor->visitAssignmentExpression($this);
    }
}
