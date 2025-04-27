<?php

declare(strict_types=1);

namespace thomas\phplox\ast_gen;

class TypeDefinition
{
    public string $typeName = "";
    /** @var array<string> $dependencies */
    public array $dependencies = [];
    /** @var array<PropertyDefinition> $properties */
    public array $properties = [];

    /**
     * @param array<string> $dependencies
     * @param array<PropertyDefinition> $properties
     */
    public function __construct(string $typeName, array $dependencies, array $properties)
    {
        $this->typeName = $typeName;
        $this->dependencies = $dependencies;
        $this->properties = $properties;
    }

    public function constructorSignature() : string
    {
        $signature = '    public function __construct(';
        $params = [];
        $docLines = [];
        foreach($this->properties as $property)
        {
            if (null !== $property->stanTypeName)
            {
                $docLines[] = "     * @param {$property->stanTypeName} \${$property->name}";
            }
            $params[] = "{$property->typeName} \${$property->name}";
        }
        $signature .= join(', ', $params);
        $signature .= ')';

        if (! empty($docLines))
        {
            $docLines = [
                '    /**',
                ...$docLines,
                '     */'
            ];
            $docBlock = join(PHP_EOL, $docLines);

            $signature = join(PHP_EOL, [$docBlock, $signature]);
        }

        return $signature;
    }

    public function fieldList() : string
    {
        $properties = [];
        foreach($this->properties as $property)
        {
            if (null !== $property->stanTypeName)
            {
                $properties[] = "    /** @var {$property->stanTypeName} \${$property->name} */";
            }
            $properties[] = "    public {$property->typeName} \${$property->name};";
        }

        return join(PHP_EOL, $properties);
    }

    public function assignmentList() : string
    {
        $assignments = [];
        foreach($this->properties as $property)
        {
            $assignments[] = "        \$this->{$property->name} = \${$property->name};";
        }

        return join(PHP_EOL, $assignments);
    }
}