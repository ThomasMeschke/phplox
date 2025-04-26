<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

/**
 * @template T
 */
interface IExpressionVisitor
{
    /**
     * @return T
     */
    function visitAssignmentExpression(AssignmentExpression $assignmentExpression) : mixed;
    /**
     * @return T
     */
    function visitBinaryExpression(BinaryExpression $binaryExpression) : mixed;
    /**
     * @return T
     */
    function visitGroupingExpression(GroupingExpression $groupingExpression) : mixed;
    /**
     * @return T
     */
    function visitLiteralExpression(LiteralExpression $literalExpression) : mixed;
    /**
     * @return T
     */
    function visitUnaryExpression(UnaryExpression $unaryExpression) : mixed;
    /**
     * @return T
     */
    function visitVariableExpression(VariableExpression $variableExpression) : mixed;
}
