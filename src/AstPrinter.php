<?php

declare(strict_types=1);

namespace thomas\phplox\src;

use thomas\phplox\src\ast\BinaryExpression;
use thomas\phplox\src\ast\Expression;
use thomas\phplox\src\ast\GroupingExpression;
use thomas\phplox\src\ast\IExpressionVisitor;
use thomas\phplox\src\ast\LiteralExpression;
use thomas\phplox\src\ast\UnaryExpression;

/**
 * @implements IExpressionVisitor<string>
 */
class AstPrinter implements IExpressionVisitor
{
    public function print(Expression $expression) : string
    {
        return $expression->accept($this) . PHP_EOL;
    }

    /**
     * @return string
     */
    public function visitBinaryExpression(BinaryExpression $binaryExpression): mixed
    {
        return $this->parenthesize(
            $binaryExpression->operator->lexeme,
            $binaryExpression->left,
            $binaryExpression->right
        );
    }

    /**
     * @return string
     */
    public function visitGroupingExpression(GroupingExpression $groupingExpression): mixed
    {
        return $this->parenthesize(
            "group",
            $groupingExpression->expression
        );
    }

    /**
     * @return string
     */
    public function visitLiteralExpression(LiteralExpression $literalExpression): mixed
    {
        if ($literalExpression->value === null) return "nil";
        if ($literalExpression->value === true) return "true";
        if ($literalExpression->value === false) return "false";

        return "{$literalExpression->value}";
    }

    /**
     * @return string
     */
    public function visitUnaryExpression(UnaryExpression $unaryExpression): mixed
    {
        return $this->parenthesize(
            $unaryExpression->operator->lexeme,
            $unaryExpression->right
        );
    }

    private function parenthesize(string $name, Expression ...$expressions) : string
    {
        $result = "({$name}";
        foreach($expressions as $expression)
        {
            $result .= ' ';
            $result .= $expression->accept($this);
        }
        $result .= ')';

        return $result;
    }
}