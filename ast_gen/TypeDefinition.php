<?php

declare(strict_types=1);

namespace thomas\phplox\ast_gen;

class TypeDefinition
{
    public string $typeName = "";
    /** @var array<string> $dependencies */
    public array $dependencies = [];
    /** @var array<string, string> $fields */
    public array $fields = [];

    /**
     * @param array<string> $dependencies
     * @param array<string, string> $fields
     */
    public function __construct(string $typeName, array $dependencies, array $fields)
    {
        $this->typeName = $typeName;
        $this->dependencies = $dependencies;
        $this->fields = $fields;
    }

    public function constructorSignature() : string
    {
        $signature = '    public function __construct(';
        $params = [];
        $docLines = [];
        foreach($this->fields as $name => $type)
        {
            if ($type === 'mixed')
            {
                $docLines[] = "     * @param scalar|null \${$name}";
            }
            $params[] = "{$type} \${$name}";
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
        $fields = [];
        foreach($this->fields as $name => $type)
        {
            if ($type === 'mixed')
            {
                $fields[] = "    /** @var scalar|null \${$name} */";
            }
            $fields[] = "    public {$type} \${$name};";
        }

        return join(PHP_EOL, $fields);
    }

    public function assignmentList() : string
    {
        $assignments = [];
        foreach(array_keys($this->fields) as $name)
        {
            $assignments[] = "        \$this->{$name} = \${$name};";
        }

        return join(PHP_EOL, $assignments);
    }
}