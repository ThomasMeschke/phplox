<?php

declare(strict_types=1);

namespace thomas\phplox\src\ast;

/**
 * @template T
 */
interface IStatementVisitor
{
    /**
     * @return T
     */
    function visitBlockStatement(BlockStatement $blockStatement) : mixed;
    /**
     * @return T
     */
    function visitExpressionStatement(ExpressionStatement $expressionStatement) : mixed;
    /**
     * @return T
     */
    function visitFunctionStatement(FunctionStatement $functionStatement) : mixed;
    /**
     * @return T
     */
    function visitIfStatement(IfStatement $ifStatement) : mixed;
    /**
     * @return T
     */
    function visitPrintStatement(PrintStatement $printStatement) : mixed;
    /**
     * @return T
     */
    function visitReturnStatement(ReturnStatement $returnStatement) : mixed;
    /**
     * @return T
     */
    function visitWhileStatement(WhileStatement $whileStatement) : mixed;
    /**
     * @return T
     */
    function visitVarStatement(VarStatement $varStatement) : mixed;
}
