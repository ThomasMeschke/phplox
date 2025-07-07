<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use DivisionByZeroError;
use Lox;
use thomas\phplox\src\ast\AssignmentExpression;
use thomas\phplox\src\ast\BinaryExpression;
use thomas\phplox\src\ast\BlockStatement;
use thomas\phplox\src\ast\CallExpression;
use thomas\phplox\src\ast\Expression;
use thomas\phplox\src\ast\ExpressionStatement;
use thomas\phplox\src\ast\FunctionStatement;
use thomas\phplox\src\ast\GroupingExpression;
use thomas\phplox\src\ast\IExpressionVisitor;
use thomas\phplox\src\ast\IfStatement;
use thomas\phplox\src\ast\IStatementVisitor;
use thomas\phplox\src\ast\LiteralExpression;
use thomas\phplox\src\ast\LogicalExpression;
use thomas\phplox\src\ast\PrintStatement;
use thomas\phplox\src\ast\ReturnStatement;
use thomas\phplox\src\ast\Statement;
use thomas\phplox\src\ast\UnaryExpression;
use thomas\phplox\src\ast\VariableExpression;
use thomas\phplox\src\ast\VarStatement;
use thomas\phplox\src\ast\WhileStatement;
use thomas\phplox\src\exceptions\ReturnException;
use thomas\phplox\src\exceptions\RuntimeErrorException;

/**
 * @implements IExpressionVisitor<mixed>
 * @implements IStatementVisitor<null>
 */
class Interpreter implements IExpressionVisitor, IStatementVisitor
{
    public readonly Environment $globals;

    /**
     * @var array<string, int> $locals
     */
    private array $locals;
    private Environment $environment;

    public function __construct()
    {
        $this->globals = new Environment();
        $this->locals = [];
        $this->environment = $this->globals;

        $this->globals->define("clock", new class implements LoxCallable {
            public function arity(): int
            {
                return 0;
            }

            /**
             * @param array<mixed> $arguments
             */
            public function call(Interpreter $interpreter, array $arguments) : mixed
            {
                return gettimeofday(as_float: TRUE);
            }

            public function __toString()
            {
                return "<native fn>";
            }
        });
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

    public function resolve(Expression $expression, int $depth): void
    {
        $this->locals[spl_object_hash($expression)] = $depth;
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
    public function visitIfStatement(IfStatement $ifStatement): mixed
    {
        if ($this->isTruthy($this->evaluate($ifStatement->condition)))
        {
            $this->execute($ifStatement->thenBranch);
        }
        else if (null !== $ifStatement->elseBranch)
        {
            $this->execute($ifStatement->elseBranch);
        }

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
    public function visitReturnStatement(ReturnStatement $returnStatement): mixed
    {
        $value = null;
        if ($returnStatement->value !== null)
        {
            $value = $this->evaluate($returnStatement->value);
        }

        throw new ReturnException($value);
    }

    /**
     * @return null
     */
    public function visitWhileStatement(WhileStatement $whileStatement): mixed
    {
        while ($this->isTruthy($this->evaluate($whileStatement->condition)))
        {
            $this->execute($whileStatement->body);
        }

        return null;
    }

    /**
     * @return null
     */
    public function visitFunctionStatement(FunctionStatement $functionStatement): mixed
    {
        $function = new LoxFunction($functionStatement, $this->environment);
        $this->environment->define($functionStatement->name->lexeme, $function);
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

    public function visitAssignmentExpression(AssignmentExpression $assignmentExpression): mixed
    {
        $value = $this->evaluate($assignmentExpression->value);

        $distance = $this->locals[spl_object_hash($assignmentExpression)] ?? null;
        if ($distance !== null)
        {
            $this->environment->assignAt($distance, $assignmentExpression->name, $value);
        }
        else
        {
            $this->globals->assign($assignmentExpression->name, $value);
        }

        return $value;
    }

    public function visitBinaryExpression(BinaryExpression $binaryExpression) : mixed
    {
        $left = $this->evaluate($binaryExpression->left);
        $right = $this->evaluate($binaryExpression->right);

        switch($binaryExpression->operator->type)
        {
            case TokenType::GREATER:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                /** @var numeric $left */
                /** @var numeric $right */
                return (double)$left > (double)$right;
            case TokenType::GREATER_EQUAL:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                /** @var numeric $left */
                /** @var numeric $right */
                return (double)$left >= (double)$right;
            case TokenType::LESS:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                /** @var numeric $left */
                /** @var numeric $right */
                return (double)$left < (double)$right;
            case TokenType::LESS_EQUAL:
                /** @var numeric $left */
                /** @var numeric $right */
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                return (double)$left <= (double)$right;

            case TokenType::MINUS:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                /** @var numeric $left */
                /** @var numeric $right */
                return (double)$left - (double)$right;
            case TokenType::PLUS:
                if (is_numeric($left) && is_numeric($right))
                {
                    /** @var numeric $left */
                    /** @var numeric $right */
                    return (double)$left + (double)$right;
                }
                if (is_string($left) || is_string($right))
                {
                    return $this->stringify($left) . $this->stringify($right);
                }
                throw new RuntimeErrorException(
                    $binaryExpression->operator,
                    "Operands must be two numbers or at least one string."
                );
            case TokenType::SLASH:
                $this->checkNumberOperands($binaryExpression->operator, $left, $right);
                /** @var numeric $left */
                /** @var numeric $right */
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
                /** @var numeric $left */
                /** @var numeric $right */
                return (double)$left * (double)$right;

            case TokenType::BANG_EQUAL:
                return ! ($left === $right);
            case TokenType::EQUAL_EQUAL:
                return $left === $right;
        }

        // Unreachable
        return null;
    }

    public function visitCallExpression(CallExpression $callExpression): mixed
    {
        $callee = $this->evaluate($callExpression->callee);

        $arguments = [];
        foreach($callExpression->arguments as $argument)
        {
            $arguments[] = $this->evaluate($argument);
        }

        if (! ($callee instanceof LoxCallable))
        {
            throw new RuntimeErrorException($callExpression->paren, "Can only call functions and classes.");
        }

        /** @var LoxCallable $function */
        $function = $callee;
        $expectedArgumentCount = $function->arity();
        $actualArgumentCount = count($arguments);
        if ($actualArgumentCount !== $expectedArgumentCount)
        {
            throw new RuntimeErrorException(
                $callExpression->paren,
                "Expected {$expectedArgumentCount} arguments but got {$actualArgumentCount}."
            );
        }

        return $function->call($this, $arguments);
    }

    public function visitGroupingExpression(GroupingExpression $groupingExpression) : mixed
    {
        return $this->evaluate($groupingExpression->expression);
    }

    public function visitLiteralExpression(LiteralExpression $literalExpression) : mixed
    {
        return $literalExpression->value;
    }

    public function visitLogicalExpression(LogicalExpression $logicalExpression) : mixed
    {
        $left = $this->evaluate($logicalExpression->left);

        if ($logicalExpression->operator->type == TokenType::OR)
        {
            // short-circuit in the case of OR:
            // if the left-hand-side is already truthy,
            // there is no need to evaluate the right hand side
            if ($this->isTruthy($left)) return $left;
        }
        else
        {
            // short-circuit in the case of AND:
            // if the left-hand-side is already falsy,
            // there is no need to evaluate the right hand side
            if (! $this->isTruthy($left)) return $left;
        }

        // if we are here, we either have
        // a falsy LHS and a logical OR,
        // or we have
        // a truthy LHS and a logical AND.
        // Either way, the RHS determines the outcome.
        return $this->evaluate($logicalExpression->right);
    }

    public function visitUnaryExpression(UnaryExpression $unaryExpression) : mixed
    {
        $right = $this->evaluate($unaryExpression->right);

        switch($unaryExpression->operator->type)
        {
            case TokenType::BANG:
                return ! $this->isTruthy($right);
            case TokenType::MINUS:
                $this->checkNumberOperand($unaryExpression->operator, $right);
                /** @var numeric $right */
                return -(double)$right;
        }

        // Unreachable
        return null;
    }

    public function visitVariableExpression(VariableExpression $variableExpression): mixed
    {
        return $this->lookUpVariable($variableExpression->name, $variableExpression);
    }

    public function checkNumberOperand(Token $operator, mixed $operand): void
    {
        if (is_numeric($operand)) return;
        throw new RuntimeErrorException($operator, "Operand must be a number.");
    }

    public function checkNumberOperands(Token $operator, mixed $left, mixed $right): void
    {
        if (is_numeric($left) && is_numeric($right)) return;
        throw new RuntimeErrorException($operator, "Operands must be numbers.");
    }

    /**
     * @param array<Statement> $statements
     */
    public function executeBlock(array $statements, Environment $environment) : void
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

    private function isTruthy(mixed $object) : bool
    {
        if ($object === null) return false;
        if (is_bool($object)) return $object;
        return true;
    }

    private function evaluate(Expression $expression) : mixed
    {
        return $expression->accept($this);
    }

    private function execute(Statement $statement) : void
    {
        $statement->accept($this);
    }

    private function lookUpVariable(Token $name, Expression $expression): mixed
    {
        $distance = $this->locals[spl_object_hash($expression)] ?? null;
        if ($distance !== null)
        {
            return $this->environment->getAt($distance, $name->lexeme);
        }

        return $this->globals->get($name);
    }

    private function stringify(mixed $value) : string
    {
        if ($value === null) return "nil";
        if (is_bool($value)) return $value ? "true" : "false";

        /**
         * $value is also allowed to be an object
         * implementing the __toString() method here.
         * I just don't have no clue how to type-hint this...
         **/

        /** @var resource|scalar $value */
        return strval($value);
    }
}