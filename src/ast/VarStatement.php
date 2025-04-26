<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

use thomas\phplox\src\Token;

class VarStatement extends Statement
{
    public Token $name;
    public ?Expression $initializer;

    public function __construct(Token $name, ?Expression $initializer)
    {
        $this->name = $name;
        $this->initializer = $initializer;
    }

    /**
     * @template T
     * @param IStatementVisitor<T> $visitor
     * @return T
     */
    public function accept(IStatementVisitor $visitor) : mixed
    {
        return $visitor->visitVarStatement($this);
    }
}
