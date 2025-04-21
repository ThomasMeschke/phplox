<?php

declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use thomas\phplox\ast_gen\TypeDefinition;
use thomas\phplox\ast_gen\TypeDefinitionCollection;

new ASTGenerator($argc, $argv);

class ASTGenerator
{
    private const EX_USAGE = 64;
    private const EX_IOERR = 74;

    /**
     * @param array<string> $argv
     */
    public function __construct(int $argc, array $argv)
    {
        if ($argc !== 2)
        {
            echo "Usage: {$argv[0]} <output directory>";
            exit (self::EX_USAGE);
        }

        $outputDir = $argv[1];
        $this->defineAST($outputDir, new TypeDefinitionCollection("Expression", [
            new TypeDefinition('Binary', ['thomas\\phplox\\src\\Token'], [
                'left' => 'Expression',
                'operator' => 'Token',
                'right' => 'Expression'
            ]),
            new TypeDefinition('Grouping', [], [
                'expression' => 'Expression'
            ]),
            new TypeDefinition('Literal', [], [
                'value' => 'mixed'
            ]),
            new TypeDefinition('Unary', ['thomas\\phplox\\src\\Token'], [
                'operator' => 'Token',
                'right' => 'Expression'
            ]),
        ]));
    }

    private function defineAST(string $outputDir, TypeDefinitionCollection $typeDefinitionCollection)
    {
        $baseTypeName = $typeDefinitionCollection->baseTypeName;
        $this->defineBaseType($outputDir, $baseTypeName);

        foreach($typeDefinitionCollection->typeDefinitions as $typeDefinition)
        {
            $this->defineType($outputDir, $baseTypeName, $typeDefinition);
        }
    }

    private function defineBaseType(string $outputDir, string $baseTypeName) : void
    {
        $path = $outputDir . DIRECTORY_SEPARATOR . "{$baseTypeName}.php";
        $fileHandle = fopen($path, 'w');

        if ($fileHandle === false)
        {
            echo "Opening file for generation failed: '{$path}'";
            exit (self::EX_IOERR);
        }

        /** @var resource $fileHandle */
        $this->appendLineToFile($fileHandle, "<?php");
        $this->appendLineToFile($fileHandle);
        $this->appendLineToFile($fileHandle, "declare(strict_types=1);");
        $this->appendLineToFile($fileHandle);
        $this->appendLineToFile($fileHandle, "namespace thomas\\phplox\\src\\ast;");
        $this->appendLineToFile($fileHandle);
        $this->appendLineToFile($fileHandle, "abstract class {$baseTypeName}");
        $this->appendLineToFile($fileHandle, "{");
        $this->appendLineToFile($fileHandle);
        $this->appendLineToFile($fileHandle, "}");

        fclose($fileHandle);
    }

    /**
     * @params array<string> $parameters
     */
    private function defineType(string $outputDir, string $baseTypeName, TypeDefinition $typeDefinition) : void
    {
        $typeName = $typeDefinition->typeName;

        $path = $outputDir . DIRECTORY_SEPARATOR . "{$typeName}{$baseTypeName}.php";
        $fileHandle = fopen($path, 'w');

        if ($fileHandle === false)
        {
            echo "Opening file for generation failed: '{$path}'";
            exit (self::EX_IOERR);
        }

        /** @var resource $fileHandle */
        $this->appendLineToFile($fileHandle, "<?php");
        $this->appendLineToFile($fileHandle);
        $this->appendLineToFile($fileHandle, "declare(strict_types=1);");
        $this->appendLineToFile($fileHandle);
        $this->appendLineToFile($fileHandle, "namespace thomas\\phplox\\src\\ast;");
        $this->appendLineToFile($fileHandle);

        foreach ($typeDefinition->namespaces as $namespace)
        {
            $this->appendLineToFile($fileHandle, "use {$namespace};");
        }

        if (! empty($typeDefinition->namespaces))
        {
            $this->appendLineToFile($fileHandle);
        }
        $this->appendLineToFile($fileHandle, "class {$typeName}{$baseTypeName} extends {$baseTypeName}");
        $this->appendLineToFile($fileHandle, "{");

        $this->appendLineToFile($fileHandle, $typeDefinition->fieldList());
        $this->appendLineToFile($fileHandle);

        $this->appendLineToFile($fileHandle, $typeDefinition->constructorSignature());
        $this->appendLineToFile($fileHandle, "    {");
        $this->appendLineToFile($fileHandle, $typeDefinition->assignmentList());
        $this->appendLineToFile($fileHandle, "    }");
        $this->appendLineToFile($fileHandle, "}");

        fclose($fileHandle);
    }

    /** @param resource $stream */
    private function appendLineToFile(mixed $stream, string $line = "")
    {
        fwrite($stream, $line . PHP_EOL);
    }
}