<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use Lox;
use thomas\phplox\src\ast\AssignmentExpression;
use thomas\phplox\src\ast\BinaryExpression;
use thomas\phplox\src\ast\BlockStatement;
use thomas\phplox\src\ast\Expression;
use thomas\phplox\src\ast\ExpressionStatement;
use thomas\phplox\src\ast\GroupingExpression;
use thomas\phplox\src\ast\LiteralExpression;
use thomas\phplox\src\ast\PrintStatement;
use thomas\phplox\src\ast\Statement;
use thomas\phplox\src\ast\UnaryExpression;
use thomas\phplox\src\ast\VariableExpression;
use thomas\phplox\src\ast\VarStatement;
use thomas\phplox\src\exceptions\ParseException;
use thomas\phplox\src\Token;

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
            $statement = $this->declaration();
            if (null !== $statement)
            {
                $statements[] = $statement;
            }
        }

        return $statements;
    }

    private function declaration() : ?Statement
    {
        try
        {
            if ($this->match(TokenType::VAR_DECL))
            {
                return $this->varDeclaration();
            }
            return $this->statement();
        }
        catch (ParseException)
        {
            $this->synchronize();
            return null;
        }
    }

    private function statement() : Statement
    {
        if ($this->match(TokenType::PRINT))
        {
            return $this->printStatement();
        }

        if ($this->match(TokenType::LEFT_BRACE))
        {
            return new BlockStatement($this->blockStatement());
        }

        return $this->expressionStatement();
    }

    private function printStatement() : Statement
    {
        $value = $this->expression();
        $this->consume(TokenType::SEMICOLON, "Expected ';' after value.");
        return new PrintStatement($value);
    }

    private function varDeclaration() : Statement
    {
        $name = $this->consume(TokenType::IDENTIFIER, 'Identifier expected.');

        $initializer = null;
        if ($this->match(TokenType::EQUAL))
        {
            $initializer = $this->expression();
        }

        $this->consume(TokenType::SEMICOLON, "Expected ';' after variable declaration.");

        return new VarStatement($name, $initializer);
    }

    private function expressionStatement() : Statement
    {
        $expression = $this->expression();
        $this->consume(TokenType::SEMICOLON, "Expected ';' after expression.");
        return new ExpressionStatement($expression);
    }

    /**
     * @return array<Statement>
     */
    private function blockStatement() : array
    {
        $statements = [];

        while (! $this->check(TokenType::RIGHT_BRACE) && ! $this->isAtEnd())
        {
            $statement = $this->declaration();
            if (null !== $statement)
            {
                $statements[] = $statement;
            }
        }

        $this->consume(TokenType::RIGHT_BRACE, "Expected '}' after block.");
        return $statements;
    }

    private function expression() : Expression
    {
        return $this->assignment();
    }

    private function assignment() : Expression
    {
        $expression = $this->equality();

        if ($this->match(TokenType::EQUAL))
        {
            $equals = $this->previous();
            $value = $this->assignment();

            if (is_a($expression, VariableExpression::class))
            {
                $name = $expression->name;
                return new AssignmentExpression($name, $value);
            }

            $this->error($equals, "Invalid assignment target.");
        }

        return $expression;
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

        if ($this->match(TokenType::IDENTIFIER))
        {
            return new VariableExpression($this->previous());
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
                case TokenType::CLASS_DECL:
                case TokenType::FUN_DECL:
                case TokenType::VAR_DECL:
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