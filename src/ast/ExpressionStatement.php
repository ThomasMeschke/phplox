<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

class ExpressionStatement extends Statement
{
    public Expression $expression;

    public function __construct(Expression $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @template T
     * @param IStatementVisitor<T> $visitor
     * @return T
     */
    public function accept(IStatementVisitor $visitor) : mixed
    {
        return $visitor->visitExpressionStatement($this);
    }
}
