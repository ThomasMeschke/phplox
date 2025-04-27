<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

class BlockStatement extends Statement
{
    /** @var array<Statement> $statements */
    public array $statements;

    /**
     * @param array<Statement> $statements
     */
    public function __construct(array $statements)
    {
        $this->statements = $statements;
    }

    /**
     * @template T
     * @param IStatementVisitor<T> $visitor
     * @return T
     */
    public function accept(IStatementVisitor $visitor) : mixed
    {
        return $visitor->visitBlockStatement($this);
    }
}
