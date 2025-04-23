<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

abstract class Expression
{
    /**
     * @template T
     * @param IVisitor<T> $visitor
     * @return T
     */
    public abstract function accept(IVisitor $visitor) : mixed;
}
