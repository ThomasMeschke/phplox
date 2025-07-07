<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

use thomas\phplox\src\Token;

class CallExpression extends Expression
{
    public Expression $callee;
    public Token $paren;
    /** @var array<Expression> $arguments */
    public array $arguments;

    /**
     * @param array<Expression> $arguments
     */
    public function __construct(Expression $callee, Token $paren, array $arguments)
    {
        $this->callee = $callee;
        $this->paren = $paren;
        $this->arguments = $arguments;
    }

    /**
     * @template T
     * @param IExpressionVisitor<T> $visitor
     * @return T
     */
    public function accept(IExpressionVisitor $visitor) : mixed
    {
        return $visitor->visitCallExpression($this);
    }
}
