<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

class IfStatement extends Statement
{
    public Expression $condition;
    public Statement $thenBranch;
    public ?Statement $elseBranch;

    public function __construct(Expression $condition, Statement $thenBranch, ?Statement $elseBranch)
    {
        $this->condition = $condition;
        $this->thenBranch = $thenBranch;
        $this->elseBranch = $elseBranch;
    }

    /**
     * @template T
     * @param IStatementVisitor<T> $visitor
     * @return T
     */
    public function accept(IStatementVisitor $visitor) : mixed
    {
        return $visitor->visitIfStatement($this);
    }
}
