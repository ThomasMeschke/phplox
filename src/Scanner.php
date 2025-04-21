<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use Lox;

class Scanner
{
    private string $source = "";
    /** @var array<Token> $tokens */
    private array $tokens = [];
    private int $start = 0;
    private int $current = 0;
    private int $line = 1;

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    /**
     * @return array<Token>
     */
    public function scanTokens() : array
    {
        while (! $this->isAtEnd())
        {
            $this->start = $this->current;
            $this->scanToken();
        }

        array_push($this->tokens, new Token(TokenType::EOF, "", null, $this->line));

        return $this->tokens;
    }

    private function scanToken() : void
    {
        $char = $this->advance();

        switch($char)
        {
            case "(": $this->addToken(TokenType::LEFT_PAREN); break;
            case ")": $this->addToken(TokenType::RIGHT_PAREN); break;
            case "{": $this->addToken(TokenType::LEFT_BRACE); break;
            case "}": $this->addToken(TokenType::RIGHT_BRACE); break;
            case ",": $this->addToken(TokenType::COMMA); break;
            case ".": $this->addToken(TokenType::DOT); break;
            case "-": $this->addToken(TokenType::MINUS); break;
            case "+": $this->addToken(TokenType::PLUS); break;
            case ";": $this->addToken(TokenType::SEMICOLON); break;
            case "*": $this->addToken(TokenType::STAR); break;

            case "!":
                $this->addToken($this->matchNext("=") ? TokenType::BANG_EQUAL : TokenType::BANG);
                break;
            case "=":
                $this->addToken($this->matchNext("=") ? TokenType::EQUAL_EQUAL : TokenType::EQUAL);
                break;
            case "<":
                $this->addToken($this->matchNext("=") ? TokenType::LESS_EQUAL : TokenType::LESS);
                break;
            case ">":
                $this->addToken($this->matchNext("=") ? TokenType::GREATER_EQUAL : TokenType::GREATER);
                break;
            case "/":
                if ($this->matchNext("/"))
                {
                    // this marks the beginning of a comment,
                    // so consume all until the end of line or file
                    while ($this->peek() !== "\n" && ! $this->isAtEnd())
                    {
                        $this->advance();
                    }
                }
                else
                {
                    $this->addToken(TokenType::SLASH);
                }

            case " ":
            case "\t":
            case "\r":
                break;

            case "\n":
                $this->line++;
                break;

            case "\"":
                $this->scanString();
                break;

            default:
                if ($this->isDigit($char))
                {
                    $this->scanNumber();
                }
                else if ($this->isAlpha($char))
                {
                    $this->scanIdentifier();
                }
                else
                {
                    Lox::lineError($this->line, "Unexpected character '{$char}'.");
                }
                break;
        }
    }

    private function scanString() : void
    {
        while($this->peek() !== "\"" && ! $this->isAtEnd())
        {
            if ($this->peek() === "\n")
            {
                $this->line++;
            }
            $this->advance();
        }

        if ($this->isAtEnd())
        {
            Lox::lineError($this->line, "Unterminated string.");
            return;
        }

        // When we are here, we found the closing double quote,
        // so we consume it
        $this->advance();

        // Trim the quotes
        $textWithQuotes = $this->getCurrentText();
        $text = trim($textWithQuotes, "\"");
        $this->addToken(TokenType::STRING, $text);
    }

    private function scanNumber() : void
    {
        $this->consumeDigits();

        // Look for a fractional part
        if ($this->peek() === "." && $this->isDigit($this->peekNext()))
        {
            // Consume the dot
            $this->advance();
            $this->consumeDigits();
        }

        $text = $this->getCurrentText();
        $this->addToken(TokenType::NUMBER, (double)$text);
    }

    private function scanIdentifier() : void
    {
        while ($this->isAlphaNumeric($this->peek()))
        {
            $this->advance();
        }

        $text = $this->getCurrentText();
        $tokenType = Keywords::get($text);
        $tokenType ??= TokenType::IDENTIFIER;

        $this->addToken($tokenType);
    }

    private function consumeDigits() : void
    {
        while ($this->isDigit($this->peek()))
        {
            $this->advance();
        }
    }

    private function matchNext(string $expected) : bool
    {
        if ($this->isAtEnd()) return false;

        if ($this->peek() !== $expected) return false;

        $this->advance();
        return true;
    }

    private function peek() : string
    {
        if ($this->isAtEnd()) return "\0";

        return $this->source[$this->current];
    }

    private function peekNext() : string
    {
        if ($this->current + 1 >= strlen($this->source)) return "\0";

        return $this->source[$this->current + 1];
    }

    private function isDigit(string $char) : bool
    {
        return $char >= "0" && $char <= "9";
    }

    private function isAlpha(string $char) : bool
    {
        return
            ($char >= "a" && $char <= "z") ||
            ($char >= "A" && $char <= "Z") ||
            ($char === "_");
    }

    private function isAlphaNumeric(string $char) : bool
    {
        return
            $this->isAlpha($char) ||
            $this->isDigit($char);
    }

    private function advance() : string
    {
        return $this->source[$this->current++];
    }

    /**
     * @param scalar|null $literal
     */
    private function addToken(TokenType $tokenType, mixed $literal = null) : void
    {
        $text = $this->getCurrentText();
        array_push($this->tokens, new Token($tokenType, $text, $literal, $this->line));
    }

    private function isAtEnd() : bool
    {
        return $this->current >= strlen($this->source);
    }

    private function getCurrentText() : string
    {
        $length = $this->current - $this->start;
        $value = substr($this->source, $this->start, $length);

        return $value;
    }
}