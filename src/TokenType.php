<?php

declare(strict_types=1);

namespace thomas\phplox\src;

enum TokenType
{
    // single character tokens
    case LEFT_PAREN;
    case RIGHT_PAREN;
    case LEFT_BRACE;
    case RIGHT_BRACE;
    case COMMA;
    case DOT;
    case MINUS;
    case PLUS;
    case SEMICOLON;
    case SLASH;
    case STAR;

    // one or more character tokens
    case BANG;
    case BANG_EQUAL;
    case EQUAL;
    case EQUAL_EQUAL;
    case GREATER;
    case GREATER_EQUAL;
    case LESS;
    case LESS_EQUAL;

    // literals
    case IDENTIFIER;
    case STRING;
    case NUMBER;

    // keywords
    case AND;
    case CLASS_DECL;
    case ELSE;
    case FALSE;
    case FUN_DECL;
    case FOR;
    case IF;
    case NIL;
    case OR;
    case PRINT;
    case RETURN;
    case SUPER;
    case THIS;
    case TRUE;
    case VAR_DECL;
    case WHILE;

    case EOF;
}