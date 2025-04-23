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
     * @param IVisitor<T> $visitor
     * @return T
     */
    public function accept(IVisitor $visitor) : mixed
    {
        return $visitor->visitGroupingExpression($this);
    }
}
