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
            new TypeDefinition('Assignment', ['thomas\\phplox\\src\\Token'], [
                'name' => 'Token',
                'value' => 'Expression'
            ]),
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
            new TypeDefinition('Variable', ['thomas\\phplox\\src\\Token'], [
                'name' => 'Token'
            ])
        ]));

        $this->defineAST($outputDir, new TypeDefinitionCollection("Statement", [
            new TypeDefinition('Expression', [], [
                'expression' => 'Expression'
            ]),
            new TypeDefinition('Print', [], [
                'expression' => 'Expression'
            ]),
            new TypeDefinition('Var', ['thomas\\phplox\\src\\Token'], [
                'name' => 'Token',
                'initializer' => '?Expression'
            ])
        ]));
    }

    private function defineAST(string $outputDir, TypeDefinitionCollection $typeDefinitionCollection)
    {
        $baseTypeName = $typeDefinitionCollection->baseTypeName;
        $this->defineBaseType($outputDir, $baseTypeName);

        $typeDefs = $typeDefinitionCollection->typeDefinitions;

        $this->defineVisitorInterface($outputDir, $typeDefinitionCollection);

        foreach($typeDefs as $typeDefinition)
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
        $this->appendLineToFile($fileHandle, "    /**");
        $this->appendLineToFile($fileHandle, "     * @template T");
        $this->appendLineToFile($fileHandle, "     * @param I{$baseTypeName}Visitor<T> \$visitor");
        $this->appendLineToFile($fileHandle, "     * @return T");
        $this->appendLineToFile($fileHandle, "     */");
        $this->appendLineToFile($fileHandle, "    public abstract function accept(I{$baseTypeName}Visitor \$visitor) : mixed;");
        $this->appendLineToFile($fileHandle, "}");

        fclose($fileHandle);
    }

    private function defineVisitorInterface(string $outputDir, TypeDefinitionCollection $typeDefinitionCollection): void
    {
        $baseTypeName = $typeDefinitionCollection->baseTypeName;
        $typeDefinitions = $typeDefinitionCollection->typeDefinitions;

        $path = $outputDir . DIRECTORY_SEPARATOR . "I{$baseTypeName}Visitor.php";
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
        $this->appendLineToFile($fileHandle, "/**");
        $this->appendLineToFile($fileHandle, " * @template T");
        $this->appendLineToFile($fileHandle, " */");
        $this->appendLineToFile($fileHandle, "interface I{$baseTypeName}Visitor");
        $this->appendLineToFile($fileHandle, "{");

        foreach($typeDefinitions as $type)
        {
            $typeName = $type->typeName . $baseTypeName;
            $lowerTypeName = lcfirst($typeName);
            $this->appendLineToFile($fileHandle, "    /**");
            $this->appendLineToFile($fileHandle, "     * @return T");
            $this->appendLineToFile($fileHandle, "     */");
            $this->appendLineToFile($fileHandle, "    function visit{$typeName}({$typeName} \${$lowerTypeName}) : mixed;");
        }

        $this->appendLineToFile($fileHandle, "}");

        fclose($fileHandle);

    }

    /**
     * @param array<string> $parameters
     */
    private function defineType(string $outputDir, string $baseTypeName, TypeDefinition $typeDefinition) : void
    {
        $typeName = $typeDefinition->typeName.$baseTypeName;

        $path = $outputDir . DIRECTORY_SEPARATOR . "{$typeName}.php";
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

        foreach ($typeDefinition->dependencies as $dependency)
        {
            $this->appendLineToFile($fileHandle, "use {$dependency};");
        }

        if (! empty($typeDefinition->dependencies))
        {
            $this->appendLineToFile($fileHandle);
        }
        $this->appendLineToFile($fileHandle, "class {$typeName} extends {$baseTypeName}");
        $this->appendLineToFile($fileHandle, "{");

        $this->appendLineToFile($fileHandle, $typeDefinition->fieldList());
        $this->appendLineToFile($fileHandle);

        $this->appendLineToFile($fileHandle, $typeDefinition->constructorSignature());
        $this->appendLineToFile($fileHandle, "    {");
        $this->appendLineToFile($fileHandle, $typeDefinition->assignmentList());
        $this->appendLineToFile($fileHandle, "    }");
        $this->appendLineToFile($fileHandle);

        $this->appendLineToFile($fileHandle, "    /**");
        $this->appendLineToFile($fileHandle, "     * @template T");
        $this->appendLineToFile($fileHandle, "     * @param I{$baseTypeName}Visitor<T> \$visitor");
        $this->appendLineToFile($fileHandle, "     * @return T");
        $this->appendLineToFile($fileHandle, "     */");
        $this->appendLineToFile($fileHandle, "    public function accept(I{$baseTypeName}Visitor \$visitor) : mixed");
        $this->appendLineToFile($fileHandle, "    {");
        $this->appendLineToFile($fileHandle, "        return \$visitor->visit{$typeName}(\$this);");
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