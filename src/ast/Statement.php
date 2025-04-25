<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

abstract class Statement
{
    /**
     * @template T
     * @param IStatementVisitor<T> $visitor
     * @return T
     */
    public abstract function accept(IStatementVisitor $visitor) : mixed;
}
