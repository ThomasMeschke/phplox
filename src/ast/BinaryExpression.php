<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

use thomas\phplox\src\Token;

class BinaryExpression extends Expression
{
    public Expression $left;
    public Token $operator;
    public Expression $right;

    public function __construct(Expression $left, Token $operator, Expression $right)
    {
        $this->left = $left;
        $this->operator = $operator;
        $this->right = $right;
    }
}
