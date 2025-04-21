<?php

declare(strict_types=1);

namespace thomas\phplox\src;

class Token
{
    public TokenType $type;
    public string $lexeme;
    /** @var scalar|null $literal */
    public mixed $literal;
    public int $line;

    /**
     * @param scalar|null $literal
     */
    public function __construct(TokenType $type, string $lexeme, mixed $literal, int $line)
    {
        $this->type = $type;
        $this->lexeme = $lexeme;
        $this->literal = $literal;
        $this->line = $line;
    }

    public function toString() : string
    {
        return "{$this->type->name} {$this->lexeme} {$this->literal}";
    }
}