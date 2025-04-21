<?php

declare(strict_types=1);

namespace thomas\phplox\src;

class Keywords
{
    /**
     * @var array<string, TokenType> $keywords
     */
    private static array $keywords = [
        'and'       => TokenType::AND,
        'class'     => TokenType::CLASS_DEF,
        'else'      => TokenType::ELSE,
        'false'     => TokenType::FALSE,
        'fun'       => TokenType::FUN_DEF,
        'for'       => TokenType::FOR,
        'if'        => TokenType::IF,
        'nil'       => TokenType::NIL,
        'or'        => TokenType::OR,
        'print'     => TokenType::PRINT,
        'return'    => TokenType::RETURN,
        'super'     => TokenType::SUPER,
        'this'      => TokenType::THIS,
        'true'      => TokenType::TRUE,
        'var'       => TokenType::VAR_DEF,
        'while'     => TokenType::WHILE,
    ];

    public static function get(string $candidate) : ?TokenType
    {
        if (array_key_exists($candidate, self::$keywords))
        {
            return self::$keywords[$candidate];
        }

        return null;
    }
}