<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

class WhileStatement extends Statement
{
    public Expression $condition;
    public Statement $body;

    public function __construct(Expression $condition, Statement $body)
    {
        $this->condition = $condition;
        $this->body = $body;
    }

    /**
     * @template T
     * @param IStatementVisitor<T> $visitor
     * @return T
     */
    public function accept(IStatementVisitor $visitor) : mixed
    {
        return $visitor->visitWhileStatement($this);
    }
}
