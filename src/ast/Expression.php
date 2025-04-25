<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

abstract class Expression
{
    /**
     * @template T
     * @param IExpressionVisitor<T> $visitor
     * @return T
     */
    public abstract function accept(IExpressionVisitor $visitor) : mixed;
}
