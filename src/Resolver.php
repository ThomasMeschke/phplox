<?php

declare(strict_types=1);

namespace thomas\phplox\src;

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

/**
 * @implements IExpressionVisitor<null>
 * @implements IStatementVisitor<null>
 */
class Resolver implements IExpressionVisitor, IStatementVisitor
{
    private readonly Interpreter $interpreter;

    /** @var Stack<Scope> */
    private readonly Stack $scopes;
    private FunctionType $currentFunction = FunctionType::NONE;

    public function __construct(Interpreter $interpreter)
    {
        $this->interpreter = $interpreter;
        $this->scopes = new Stack();
    }

    public function visitBlockStatement(BlockStatement $blockStatement): mixed
    {
        $this->beginScope();
        $this->resolveStatements($blockStatement->statements);
        $this->endScope();

        return null;
    }

    public function visitExpressionStatement(ExpressionStatement $expressionStatement): mixed
    {
        $this->resolveExpression($expressionStatement->expression);
        return null;
    }

    public function visitFunctionStatement(FunctionStatement $functionStatement): mixed
    {
        $this->declare($functionStatement->name);
        $this->define($functionStatement->name);

        $this->resolveFunction($functionStatement, FunctionType::FUNCTION);

        return null;
    }

    public function visitIfStatement(IfStatement $ifStatement): mixed
    {
        $this->resolveExpression($ifStatement->condition);
        $this->resolveStatement($ifStatement->thenBranch);
        if ($ifStatement->elseBranch !== null)
        {
            $this->resolveStatement($ifStatement->elseBranch);
        }
        return null;
    }

    public function visitPrintStatement(PrintStatement $printStatement): mixed
    {
        $this->resolveExpression($printStatement->expression);

        return null;
    }

    public function visitReturnStatement(ReturnStatement $returnStatement): mixed
    {
        if ($this->currentFunction === FunctionType::NONE)
        {
            Lox::tokenError($returnStatement->keyword, "Cannot return from top-level code");
        }

        if ($returnStatement->value !== null)
        {
            $this->resolveExpression($returnStatement->value);
        }

        return null;
    }

    public function visitVarStatement(VarStatement $varStatement): mixed
    {
        $this->declare($varStatement->name);
        if ($varStatement->initializer !== null)
        {
            $this->resolveExpression($varStatement->initializer);
        }
        $this->define($varStatement->name);

        return null;
    }

    public function visitWhileStatement(WhileStatement $whileStatement): mixed
    {
        $this->resolveExpression($whileStatement->condition);
        $this->resolveStatement($whileStatement->body);

        return null;
    }

    public function visitVariableExpression(VariableExpression $variableExpression): mixed
    {
        if (! $this->scopes->empty())
        {
            $variableName = $variableExpression->name->lexeme;
            /** @var Scope $scope */
            $scope = $this->scopes->peek();
            if ($scope->isDeclared($variableName) && ! $scope->isDefined($variableName))
            {
                Lox::tokenError($variableExpression->name, "Cannot read local variable in its own initializer.");
            }
        }

        $this->resolveLocal($variableExpression, $variableExpression->name);

        return null;
    }

    public function visitAssignmentExpression(AssignmentExpression $assignmentExpression): mixed
    {
        $this->resolveExpression($assignmentExpression->value);
        $this->resolveLocal($assignmentExpression, $assignmentExpression->name);

        return null;
    }

    public function visitBinaryExpression(BinaryExpression $binaryExpression): mixed
    {
        $this->resolveExpression($binaryExpression->left);
        $this->resolveExpression($binaryExpression->right);

        return null;
    }

    public function visitCallExpression(CallExpression $callExpression): mixed
    {
        $this->resolveExpression($callExpression->callee);

        foreach ($callExpression->arguments as $argument)
        {
            $this->resolveExpression($argument);
        }

        return null;
    }

    public function visitGroupingExpression(GroupingExpression $groupingExpression): mixed
    {
        $this->resolveExpression($groupingExpression->expression);

        return null;
    }

    public function visitLiteralExpression(LiteralExpression $literalExpression): mixed
    {
        return null;
    }

    public function visitLogicalExpression(LogicalExpression $logicalExpression): mixed
    {
        $this->resolveExpression($logicalExpression->left);
        $this->resolveExpression($logicalExpression->right);

        return null;
    }

    public function visitUnaryExpression(UnaryExpression $unaryExpression): mixed
    {
        $this->resolveExpression($unaryExpression->right);
        return null;
    }

    private function declare(Token $name): void
    {
        if ($this->scopes->empty())
        {
            return;
        }

        /** @var Scope $scope */
        $scope = $this->scopes->peek();

        $variableName = $name->lexeme;
        if ($scope->isDeclared($variableName))
        {
            Lox::tokenError($name, "A variable named '{$variableName}' is already declared in the same scope.");
        }

        $scope->declare($name->lexeme);
    }

    private function define(Token $name): void
    {
        if ($this->scopes->empty())
        {
            return;
        }

        /** @var Scope $scope */
        $scope = $this->scopes->peek();
        $scope->define($name->lexeme);
    }

    private function resolveFunction(FunctionStatement $functionStatement, FunctionType $functionType): void
    {
        $enclosingFunction = $this->currentFunction;
        $this->currentFunction = $functionType;

        $this->beginScope();

        foreach ($functionStatement->parameters as $parameter)
        {
            $this->declare($parameter);
            $this->define($parameter);
        }
        $this->resolveStatements($functionStatement->body);

        $this->endScope();
        $this->currentFunction = $enclosingFunction;
    }

    /**
     * @param array<Statement> $statements
     */
    public function resolveStatements(array $statements): void
    {
        foreach ($statements as $statement)
        {
            $this->resolveStatement($statement);
        }
    }

    private function resolveStatement(Statement $statement): void
    {
        $statement->accept($this);
    }

    private function resolveExpression(Expression $expression): void
    {
        $expression->accept($this);
    }

    private function resolveLocal(Expression $expression, Token $name): void
    {
        $innermostScopeIndex = $this->scopes->size() - 1;
        for ($i = $innermostScopeIndex; $i >= 0; $i--)
        {
            // walk the scopes from innermost to outermost

            /** @var Scope|null $scope */
            $scope = $this->scopes->get($i);
            if ($scope !== null && $scope->isDeclared($name->lexeme))
            {
                // if we found a scope that knows a variable with that name,
                // remember how far away the declaring scope is from the using scope.
                $resolutionScopeDistance = $this->scopes->size() - 1 - $i;
                $this->interpreter->resolve($expression, $resolutionScopeDistance);
                return;
            }
        }
        // if we never find a scope with the variable in question,
        // we leave it unresolved and assume it is global.
    }

    private function beginScope(): void
    {
        $this->scopes->push(new Scope());
    }

    private function endScope(): void
    {
        $this->scopes->pop();
    }
}