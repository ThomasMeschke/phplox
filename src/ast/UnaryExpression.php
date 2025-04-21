<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

use thomas\phplox\src\Token;

class UnaryExpression extends Expression
{
    public Token $operator;
    public Expression $right;

    public function __construct(Token $operator, Expression $right)
    {
        $this->operator = $operator;
        $this->right = $right;
    }
}
