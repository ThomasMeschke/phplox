<?php

declare(strict_types=1);

namespace thomas\phplox\src\exceptions;

use Exception;
use thomas\phplox\src\Token;

class RuntimeErrorException extends Exception
{
    public Token $token;

    public function __construct(Token $token, string $message)
    {
        parent::__construct($message);
        $this->token = $token;
    }
}