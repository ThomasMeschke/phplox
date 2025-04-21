<?php

declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use thomas\phplox\src\Scanner;

new Lox($argc, $argv);

class Lox
{
    private const EX_USAGE      = 64;
    private const EX_DATAERR    = 65;
    private const EX_IOERR      = 74;

    private static bool $hadError = false;

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
        else if ($argc == 2)
        {
            $this->runFile($argv[1]);
        }
        else
        {
            $this->runPrompt();
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

        if (self::$hadError)
        {
            exit(self::EX_DATAERR);
        }
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

        foreach($tokens as $token)
        {
            echo $token->toString() . PHP_EOL;
        }
    }

    public static function error(int $line, string $message) : void
    {
        self::report($line, "", $message);
    }

    private static function report(int $line, string $where, string $message) : void
    {
        echo "[line {$line}] Error {$where}: {$message}" . PHP_EOL;
        self::$hadError = true;
    }
}
