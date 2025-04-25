<?php

declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use thomas\phplox\src\AstPrinter;
use thomas\phplox\src\exceptions\RuntimeErrorException;
use thomas\phplox\src\Interpreter;
use thomas\phplox\src\Parser;
use thomas\phplox\src\Scanner;
use thomas\phplox\src\Token;
use thomas\phplox\src\TokenType;

new Lox($argc, $argv);

class Lox
{
    private const EX_USAGE      = 64;
    private const EX_DATAERR    = 65;
    private const EX_SOFTWARE   = 70;
    private const EX_IOERR      = 74;

    private static Interpreter $interpreter;
    private static bool $hadError = false;
    private static bool $hadRuntimeError = false;

    /**
     * @param array<string> $argv
     */
    public function __construct(int $argc, array $argv)
    {
        // $argc is the amount of arguments INCLUDING the script name itself
        // $argv is the array of arguments, with the first element being the script name

        if ($argc > 2)
        {
            echo "Usage: {$argv[0]} [script]" . PHP_EOL;
            exit(self::EX_USAGE);
        }
        else
        {
            self::$interpreter = new Interpreter();

            if ($argc == 2)
            {
                $this->runFile($argv[1]);
            }
            else
            {
                $this->runPrompt();
            }
        }
    }

    private function runFile(string $filePath) : void
    {
        if (! is_file(($filePath)))
        {
            echo "Failed to open file" . PHP_EOL;
            exit(self::EX_IOERR);
        }

        $fileContents = file_get_contents($filePath);
        $this->run($fileContents);

        if (self::$hadError)        exit(self::EX_DATAERR);
        if (self::$hadRuntimeError) exit(self::EX_SOFTWARE);
    }

    private function runPrompt() : void
    {
        for(;;)
        {
            $line = readline("> ");
            if($line === "exit") break;

            $this->run($line);
            self::$hadError = false;
        }
    }

    private function run(string $source) : void
    {
        $scanner = new Scanner($source);
        $tokens = $scanner->scanTokens();

        $parser = new Parser($tokens);
        $statements = $parser->parse();

        if (self::$hadError) return;

        self::$interpreter->interpret($statements);
    }

    public static function lineError(int $line, string $message) : void
    {
        self::report($line, "", $message);
    }

    public static function tokenError(Token $token, string $message) : void
    {
        if ($token->type === TokenType::EOF)
        {
            self::report($token->line, "at end", $message);
        }
        else
        {
            self::report($token->line, "at '{$token->lexeme}'", $message);
        };
    }

    public static function RuntimeError(RuntimeErrorException $error) : void
    {
        $message = $error->getMessage();
        $line = $error->token->line;
        echo "[line {$line}] Runtime Error: {$message}" . PHP_EOL;
        self::$hadRuntimeError = true;
    }

    private static function report(int $line, string $where, string $message) : void
    {
        echo "[line {$line}] Error {$where}: {$message}" . PHP_EOL;
        self::$hadError = true;
    }
}
