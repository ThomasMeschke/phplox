<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

class LiteralExpression extends Expression
{
    /**
     * @var scalar|null $value
     */
    public mixed $value;

    /**
     * @param scalar|null $value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }
}
