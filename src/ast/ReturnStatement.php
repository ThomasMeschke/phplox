<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

use thomas\phplox\src\Token;

class ReturnStatement extends Statement
{
    public Token $keyword;
    public ?Expression $value;

    public function __construct(Token $keyword, ?Expression $value)
    {
        $this->keyword = $keyword;
        $this->value = $value;
    }

    /**
     * @template T
     * @param IStatementVisitor<T> $visitor
     * @return T
     */
    public function accept(IStatementVisitor $visitor) : mixed
    {
        return $visitor->visitReturnStatement($this);
    }
}
