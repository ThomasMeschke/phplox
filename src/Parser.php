<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use Lox;
use thomas\phplox\src\ast\BinaryExpression;
use thomas\phplox\src\ast\Expression;
use thomas\phplox\src\ast\ExpressionStatement;
use thomas\phplox\src\ast\GroupingExpression;
use thomas\phplox\src\ast\LiteralExpression;
use thomas\phplox\src\ast\PrintStatement;
use thomas\phplox\src\ast\Statement;
use thomas\phplox\src\ast\UnaryExpression;
use thomas\phplox\src\exceptions\ParseException;
use thomas\phplox\src\Token;

/**
 * GRAMMAR:
 *  program        → statement* EOF ;
 *  statement      → exprStmt
 *                 | printStmt ;
 *  exprStmt       → expression ";" ;
 *  printStmt      → "print" expression ";" ;
 *  expression     → equality ;
 *  equality       → comparison ( ( "!=" | "==" ) comparison )* ;
 *  comparison     → term ( ( ">" | ">=" | "<" | "<=" ) term )* ;
 *  term           → factor ( ( "-" | "+" ) factor )* ;
 *  factor         → unary ( ( "/" | "*" ) unary )* ;
 *  unary          → ( "!" | "-" ) unary
 *                 | primary ;
 *  primary        → NUMBER | STRING | "true" | "false" | "nil"
 *                 | "(" expression ")" ;
 */

class Parser
{
    /** @var array<Token> $tokens */
    private array $tokens;
    private int $current = 0;

    /**
     * @param array<Token> $tokens
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @return array<Statement>
     */
    public function parse() : array
    {
        /** @var array<Statement> $statements */
        $statements = [];

        while (! $this->isAtEnd())
        {
            array_push($statements, $this->statement());
        }

        return $statements;
    }

    private function statement() : Statement
    {
        if ($this->match(TokenType::PRINT))
        {
            return $this->printStatement();
        }

        return $this->expressionStatement();
    }

    private function printStatement() : Statement
    {
        $value = $this->expression();
        $this->consume(TokenType::SEMICOLON, "Expected ';' after value.");
        return new PrintStatement($value);
    }

    private function expressionStatement() : Statement
    {
        $expression = $this->expression();
        $this->consume(TokenType::SEMICOLON, "Expected ';' after expression.");
        return new ExpressionStatement($expression);
    }

    private function expression() : Expression
    {
        return $this->equality();
    }

    private function equality() : Expression
    {
        $left = $this->comparison();

        while ($this->match(TokenType::BANG_EQUAL, TokenType::EQUAL_EQUAL))
        {
            $operator = $this->previous();
            $right = $this->comparison();

            $left =new BinaryExpression($left, $operator, $right);
        }

        return $left;
    }

    private function comparison() : Expression
    {
        $left = $this->term();

        while ($this->match(TokenType::GREATER, TokenType::GREATER_EQUAL, TokenType::LESS, TokenType::LESS_EQUAL))
        {
            $operator = $this->previous();
            $right = $this->term();

            $left = new BinaryExpression($left, $operator, $right);
        }

        return $left;
    }

    private function term() : Expression
    {
        $left = $this->factor();

        while ($this->match(TokenType::MINUS, TokenType::PLUS))
        {
            $operator = $this->previous();
            $right = $this->factor();

            $left = new BinaryExpression($left, $operator, $right);
        }

        return $left;
    }

    private function factor() : Expression
    {
        $left = $this->unary();

        while ($this->match(TokenType::SLASH, TokenType::STAR))
        {
            $operator = $this->previous();
            $right = $this->unary();

            $left = new BinaryExpression($left, $operator, $right);
        }

        return $left;
    }

    private function unary() : Expression
    {
        if ($this->match(TokenType::BANG, TokenType::MINUS))
        {
            $operator = $this->previous();
            $right = $this->unary();

            return new UnaryExpression($operator, $right);
        }

        return $this->primary();
    }

    private function primary() : Expression
    {
        if ($this->match(TokenType::FALSE)) return new LiteralExpression(false);
        if ($this->match(TokenType::TRUE)) return new LiteralExpression(true);
        if ($this->match(TokenType::NIL)) return new LiteralExpression(null);

        if ($this->match(TokenType::NUMBER , TokenType::STRING))
        {
            return new LiteralExpression($this->previous()->literal);
        }

        if ($this->match(TokenType::LEFT_PAREN))
        {
            $expression = $this->expression();
            $this->consume(TokenType::RIGHT_PAREN, "Expected ')' after expression.");

            return new GroupingExpression($expression);
        }

        throw $this->error($this->peek(), "Expected expression.");
    }

    private function match(TokenType ...$tokenTypes) : bool
    {
        foreach($tokenTypes as $tokenType)
        {
            if ($this->check($tokenType))
            {
                $this->advance();
                return true;
            }
        }

        return false;
    }

    private function consume(TokenType $tokenType, String $errorMessage) : Token
    {
        if ($this->check($tokenType)) return $this->advance();

        throw $this->error($this->peek(), $errorMessage);
    }

    private function check(TokenType $tokenType) : bool
    {
        if ($this->isAtEnd()) return false;

        return $this->peek()->type == $tokenType;
    }

    private function advance() : Token
    {
        if (! $this->isAtEnd()) $this->current++;

        return $this->previous();
    }

    private function isAtEnd() : bool
    {
        return $this->peek()->type === TokenType::EOF;
    }

    private function peek() : Token
    {
        return $this->tokens[$this->current];
    }

    private function previous() : Token
    {
        return $this->tokens[$this->current - 1];
    }

    private function error(Token $token, String $message) : ParseException
    {
        Lox::tokenError($token, $message);
        return new ParseException();
    }

    // @phpstan-ignore method.unused
    private function synchronize() : void
    {
        // Discard all comming tokens
        // until we think we have reached a statement boundary

        $this->advance();

        while (! $this->isAtEnd())
        {
            if ($this->previous()->type === TokenType::SEMICOLON) return;

            switch ($this->peek()->type)
            {
                case TokenType::CLASS_DEF:
                case TokenType::FUN_DEF:
                case TokenType::VAR_DEF:
                case TokenType::FOR:
                case TokenType::IF:
                case TokenType::WHILE:
                case TokenType::PRINT:
                case TokenType::RETURN:
                    return;
            }

            $this->advance();
        }
    }
}