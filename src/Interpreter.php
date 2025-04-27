<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use DivisionByZeroError;
use Lox;
use thomas\phplox\src\ast\AssignmentExpression;
use thomas\phplox\src\ast\BinaryExpression;
use thomas\phplox\src\ast\BlockStatement;
use thomas\phplox\src\ast\Expression;
use thomas\phplox\src\ast\ExpressionStatement;
use thomas\phplox\src\ast\GroupingExpression;
use thomas\phplox\src\ast\IExpressionVisitor;
use thomas\phplox\src\ast\IStatementVisitor;
use thomas\phplox\src\ast\LiteralExpression;
use thomas\phplox\src\ast\PrintStatement;
use thomas\phplox\src\ast\Statement;
use thomas\phplox\src\ast\UnaryExpression;
use thomas\phplox\src\ast\VariableExpression;
use thomas\phplox\src\ast\VarStatement;
use thomas\phplox\src\exceptions\RuntimeErrorException;

/**
 * @implements IExpressionVisitor<scalar|null>
 * @implements IStatementVisitor<null>
 */
class Interpreter implements IExpressionVisitor, IStatementVisitor
{
    private Environment $environment;

    public function __construct()
    {
        $this->environment = new Environment();
    }

    /**
     * @param array<Statement> $statements
     */
    public function interpret(array $statements) : void
    {
        try
        {
            foreach($statements as $statement)
            {
                $this->execute($statement);
            }
        }
        catch (RuntimeErrorException $error)
        {
            Lox::runtimeError($error);
        }
    }

    /**
     * @return null
     */
    public function visitExpressionStatement(ExpressionStatement $expressionStatement) : mixed
    {
        $this->evaluate($expressionStatement->expression);
        return null;
    }

    /**
     * @return null
     */
    public function visitBlockStatement(BlockStatement $blockStatement): mixed
    {
        $this->executeBlock($blockStatement->statements, new Environment($this->environment));
        return null;
    }

    /**
     * @return null
     */
    public function visitPrintStatement(PrintStatement $printStatement) : mixed
    {
        $value = $this->evaluate($printStatement->expression);
        echo $this->stringify($value) . PHP_EOL;
        return null;
    }

    /**
     * @return null
     */
    public function visitVarStatement(VarStatement $varStatement): mixed
    {
        $value = null;
        if (null !== $varStatement->initializer)
        {
            $value = $this->evaluate($varStatement->initializer);
        }

        $this->environment->define($varStatement->name->lexeme, $value);
        return null;
    }

    /**
     * @return scalar|null
     */
    public function visitAssignmentExpression(AssignmentExpression $assignmentExpression): mixed
    {
        $value = $this->evaluate($assignmentExpression->value);
        $this->environment->assign($assignmentExpression->name, $value);
        return $value;
    }

    /**
     * @return scalar|null
     */
    public function visitBinaryExpression(BinaryExpression $binaryExpression) : mixed
    {
        $left = $this->evaluate($binaryExpression->left);
        $right = $this->evaluate($binaryExpression->right);

        switch($binaryExpression->operator->type)
        {
            case TokenType::GREATER:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                return (double)$left > (double)$right;
            case TokenType::GREATER_EQUAL:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                return (double)$left >= (double)$right;
            case TokenType::LESS:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                return (double)$left < (double)$right;
            case TokenType::LESS_EQUAL:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                return (double)$left <= (double)$right;

            case TokenType::MINUS:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                return (double)$left - (double)$right;
            case TokenType::PLUS:
                if (is_numeric($left) && is_numeric($right))
                {
                    return (double)$left + (double)$right;
                }
                if (is_string($left) && is_string($right))
                {
                    return "{$left}{$right}";
                }
                throw new RuntimeErrorException(
                    $binaryExpression->operator,
                    "Operands must be two numbers or two strings."
                );
            case TokenType::SLASH:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                try
                {
                    return (double)$left / (double)$right;
                }
                // https://github.com/phpstan/phpstan/issues/12865
                // @phpstan-ignore catch.neverThrown
                catch (DivisionByZeroError)
                {
                    throw new RuntimeErrorException(
                        $binaryExpression->operator,
                        "Division by zero."
                    );
                }
            case TokenType::STAR:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                return (double)$left * (double)$right;

            case TokenType::BANG_EQUAL:
                return ! ($left === $right);
            case TokenType::EQUAL_EQUAL:
                return $left === $right;
        }

        // Unreachable
        return null;
    }

    /**
     * @return scalar|null
     */
    public function visitGroupingExpression(GroupingExpression $groupingExpression) : mixed
    {
        return $this->evaluate($groupingExpression->expression);
    }

    /**
     * @return scalar|null
     */
    public function visitLiteralExpression(LiteralExpression $literalExpression) : mixed
    {
        return $literalExpression->value;
    }

    /**
     * @return scalar|null
     */
    public function visitUnaryExpression(UnaryExpression $unaryExpression) : mixed
    {
        $right = $this->evaluate($unaryExpression->right);

        switch($unaryExpression->operator->type)
        {
            case TokenType::BANG:
                return ! $this->isTruthy($right);
            case TokenType::MINUS:
                $this->checkNumberOperand($unaryExpression->operator, $right);
                return -(double)$right;
        }

        // Unreachable
        return null;
    }

    /**
     * @return scalar|null
     */
    public function visitVariableExpression(VariableExpression $variableExpression): mixed
    {
        return $this->environment->get($variableExpression->name);
    }

    /**
     * @param scalar|null $operand
     */
    public function checkNumberOperand(Token $operator, mixed $operand): void
    {
        if (is_numeric($operand)) return;
        throw new RuntimeErrorException($operator, "Operand must be a number.");
    }

    /**
     * @param scalar|null $left
     * @param scalar|null $right
     */
    public function checkNumberOperands(Token $operator, mixed $left, mixed $right): void
    {
        if (is_numeric($left) && is_numeric($right)) return;
        throw new RuntimeErrorException($operator, "Operands must be numbers.");
    }

    /**
     * @param scalar|null $object
     */
    private function isTruthy(mixed $object) : bool
    {
        if ($object === null) return false;
        if (is_bool($object)) return $object;
        return true;
    }

    /**
     * @return scalar|null
     */
    private function evaluate(Expression $expression)
    {
        return $expression->accept($this);
    }

    private function execute(Statement $statement) : void
    {
        $statement->accept($this);
    }

    /**
     * @param array<Statement> $statements
     */
    private function executeBlock(array $statements, Environment $environment) : void
    {
        $previousEnvironment = $this->environment;
        try
        {
            $this->environment = $environment;

            foreach($statements as $statement)
            {
                $this->execute($statement);
            }
        }
        finally
        {
            $this->environment = $previousEnvironment;
        }
    }

    /**
     * @param scalar|null $value
     */
    private function stringify(mixed $value) : string
    {
        if ($value === null) return "nil";
        if (is_bool($value)) return $value ? "true" : "false";
        return (string)$value;
    }
}