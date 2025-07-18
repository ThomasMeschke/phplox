<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use LogicException;
use Lox;
use thomas\phplox\src\ast\AssignmentExpression;
use thomas\phplox\src\ast\BinaryExpression;
use thomas\phplox\src\ast\BlockStatement;
use thomas\phplox\src\ast\CallExpression;
use thomas\phplox\src\ast\Expression;
use thomas\phplox\src\ast\ExpressionStatement;
use thomas\phplox\src\ast\FunctionStatement;
use thomas\phplox\src\ast\GroupingExpression;
use thomas\phplox\src\ast\IfStatement;
use thomas\phplox\src\ast\LiteralExpression;
use thomas\phplox\src\ast\LogicalExpression;
use thomas\phplox\src\ast\PrintStatement;
use thomas\phplox\src\ast\ReturnStatement;
use thomas\phplox\src\ast\Statement;
use thomas\phplox\src\ast\UnaryExpression;
use thomas\phplox\src\ast\VariableExpression;
use thomas\phplox\src\ast\VarStatement;
use thomas\phplox\src\ast\WhileStatement;
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
            if ($this->match(TokenType::FUN_DECL))
            {
                return $this->function(FunctionType::FUNCTION);
            }
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

    private function function(FunctionType $functionType) : Statement
    {
        $functionTypeString = $functionType->value;
        $name = $this->consume(TokenType::IDENTIFIER, "{$functionTypeString} name expected.");

        $this->consume(TokenType::LEFT_PAREN, "Expected '(' after {$functionTypeString} name.");

        /** @var array<Token> $parameters */
        $parameters =[];
        if (! $this->check(TokenType::RIGHT_PAREN))
        {
            do {
                if (count($parameters) >= 255)
                {
                    $this->error($this->peek(), "Can't have more than 255 parameters.");
                }

                $parameters[] = $this->consume(TokenType::IDENTIFIER, "Parameter name expected.");
            } while ($this->match(TokenType::COMMA));
        }

        $this->consume(TokenType::RIGHT_PAREN, "Expected ')' after parameters");
        $this->consume(TokenType::LEFT_BRACE, "Expetced '{' before {$functionTypeString} body.");

        $body = $this->blockStatement();

        return new FunctionStatement($name, $parameters, $body);
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

    private function statement() : Statement
    {
        if ($this->match(TokenType::FOR))
        {
            return $this->forStatement();
        }
        if ($this->match(TokenType::IF))
        {
            return $this->ifStatement();
        }
        if ($this->match(TokenType::PRINT))
        {
            return $this->printStatement();
        }
        if ($this->match(TokenType::RETURN))
        {
            return $this->returnStatement();
        }
        if ($this->match(TokenType::WHILE))
        {
            return $this->whileStatement();
        }
        if ($this->match(TokenType::LEFT_BRACE))
        {
            return new BlockStatement($this->blockStatement());
        }

        return $this->expressionStatement();
    }

    private function forStatement() : Statement
    {
        $this->consume(TokenType::LEFT_PAREN, "Expected '(' after 'for'.");

        $initializer = null;
        if ($this->check(TokenType::SEMICOLON))
        {
            $this->consume(TokenType::SEMICOLON, "Expected ';' after loop initializer.");
        }
        else if ($this->match(TokenType::VAR_DECL))
        {
            $initializer = $this->varDeclaration();
        }
        else
        {
            $initializer = $this->expressionStatement();
        }

        $condition = null;
        if (! $this->check(TokenType::SEMICOLON))
        {
            $condition = $this->expression();
        }
        $this->consume(TokenType::SEMICOLON, "Expected ';' after loop condition.");

        $increment = null;
        if (! $this->check(TokenType::RIGHT_PAREN))
        {
            $increment = $this->expression();
        }
        $this->consume(TokenType::RIGHT_PAREN, "Expected ')' after 'for' clauses.");

        $body = $this->statement();

        if (null !== $increment)
        {
            $body = new BlockStatement([
                $body,
                new ExpressionStatement($increment)
            ]);
        }

        if (null == $condition)
        {
            $condition = new LiteralExpression(true);
        }

        $body = new WhileStatement($condition, $body);

        if (null !== $initializer)
        {
            $body = new BlockStatement([
                $initializer,
                $body
            ]);
        }

        return $body;
    }

    private function ifStatement() : Statement
    {
        $this->consume(TokenType::LEFT_PAREN, "Expected '(' after 'if'.");
        $condition = $this->expression();
        $this->consume(TokenType::RIGHT_PAREN, "Expected ')' after 'if' condition.");

        $thenBranch = $this->statement();
        $elseBranch = null;
        if ($this->match(TokenType::ELSE))
        {
            // this inherently leads to the fact that
            // a dangling else is consumed by the if
            // that is nearest to the else.
            $elseBranch = $this->statement();
        }

        return new IfStatement($condition, $thenBranch, $elseBranch);
    }

    private function printStatement() : Statement
    {
        $value = $this->expression();
        $this->consume(TokenType::SEMICOLON, "Expected ';' after value.");
        return new PrintStatement($value);
    }

    private function returnStatement() : Statement
    {
        $keyword = $this->previous();
        $value = NULL;
        if (! $this->check(TokenType::SEMICOLON))
        {
            $value = $this->expression();
        }

        $this->consume(TokenType::SEMICOLON, "Expected ';' after return value.");

        return new ReturnStatement($keyword, $value);
    }

    private function whileStatement() : Statement
    {
        $this->consume(TokenType::LEFT_PAREN, "Expected '(' after 'while'.");
        $condition = $this->expression();
        $this->consume(TokenType::RIGHT_PAREN, "Expected ')' after 'while' condition.");

        $body = $this->statement();

        return new WhileStatement($condition, $body);
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
        $expression = $this->or();

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

    private function or() : Expression
    {
        $left = $this->and();

        while ($this->match(TokenType::OR))
        {
            $operator = $this->previous();
            $right = $this->and();

            return new LogicalExpression($left, $operator, $right);
        }

        return $left;
    }

    private function and() : Expression
    {
        $left = $this->equality();

        while ($this->match(TokenType::AND))
        {
            $operator = $this->previous();
            $right = $this->equality();

            return new LogicalExpression($left, $operator, $right);
        }

        return $left;
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

        return $this->call();
    }

    private function call(): Expression
    {
        $expression = $this->primary();

        while(true)
        {
            if($this->match(TokenType::LEFT_PAREN))
            {
                $expression = $this->finishCall($expression);
            }
            else
            {
                break;
            }
        }

        return $expression;
    }

    private function finishCall(Expression $callee): Expression
    {
        /** @var array<Expression> $arguments */
        $arguments = [];

        if (! $this->check(TokenType::RIGHT_PAREN))
        {
            do
            {
                if (count($arguments) >= 255)
                {
                    $this->error($this->peek(), "Can't have more than 255 arguments.");
                }
                $arguments[] = $this->expression();
            } while($this->match(TokenType::COMMA));
        }

        $paren = $this->consume(TokenType::RIGHT_PAREN, "Expected ')' after arguments.");

        return new CallExpression($callee, $paren, $arguments);
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