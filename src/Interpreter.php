<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use DivisionByZeroError;
use Lox;
use thomas\phplox\src\ast\BinaryExpression;
use thomas\phplox\src\ast\Expression;
use thomas\phplox\src\ast\GroupingExpression;
use thomas\phplox\src\ast\IExpressionVisitor;
use thomas\phplox\src\ast\LiteralExpression;
use thomas\phplox\src\ast\UnaryExpression;
use thomas\phplox\src\exceptions\RuntimeErrorException;

/**
 * @implements IExpressionVisitor<scalar|null>
 */
class Interpreter implements IExpressionVisitor
{
    public function interpret(Expression $expression) : void
    {
        try
        {
            $value = $this->evaluate($expression);
            echo $this->stringify($value) . PHP_EOL;
        }
        catch (RuntimeErrorException $error)
        {
            Lox::runtimeError($error);
        }
    }

    /**
     * @return scalar|null
     */
    public function visitBinaryExpression(BinaryExpression $binaryExpression): mixed
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
    public function visitGroupingExpression(GroupingExpression $groupingExpression): mixed
    {
        return $this->evaluate($groupingExpression->expression);
    }

    /**
     * @return scalar|null
     */
    public function visitLiteralExpression(LiteralExpression $literalExpression): mixed
    {
        return $literalExpression->value;
    }

    /**
     * @return scalar|null
     */
    public function visitUnaryExpression(UnaryExpression $unaryExpression): mixed
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
    private function evaluate(Expression $expression): mixed
    {
        return $expression->accept($this);
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