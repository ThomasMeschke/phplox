<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

class GroupingExpression extends Expression
{
    public Expression $expression;

    public function __construct(Expression $expression)
    {
        $this->expression = $expression;
    }

    /**
     * @template T
     * @param IExpressionVisitor<T> $visitor
     * @return T
     */
    public function accept(IExpressionVisitor $visitor) : mixed
    {
        return $visitor->visitGroupingExpression($this);
    }
}
