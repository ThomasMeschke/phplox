<?php

declare(strict_types=1);

use thomas\phplox\src\ast\BinaryExpression;
use thomas\phplox\src\ast\Expression;
use thomas\phplox\src\ast\GroupingExpression;
use thomas\phplox\src\ast\LiteralExpression;
use thomas\phplox\src\ast\UnaryExpression;
use thomas\phplox\src\AstPrinter;
use thomas\phplox\src\Token;
use thomas\phplox\src\TokenType;

require __DIR__ . '/vendor/autoload.php';

$expr = new BinaryExpression(
    new UnaryExpression(
        new Token(TokenType::MINUS, "-", null, 1),
        new LiteralExpression(123)
    ),
    new Token(TokenType::STAR, '*', null, 1),
    new GroupingExpression(
        new LiteralExpression(45.67)
    )
);

echo (new AstPrinter())->print($expr);