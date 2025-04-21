<?php

declare(strict_types=1);

namespace thomas\phplox\ast_gen;

class TypeDefinition
{
    public string $typeName = "";
    /** @var array<string> $namespaces */
    public array $namespaces = [];
    /** @var array<string, string> $fields */
    public array $fields = [];

    /**
     * @param array<string> $namespaces
     * @param array<string, string> $fields
     */
    public function __construct(string $typeName, array $namespaces, array $fields)
    {
        $this->typeName = $typeName;
        $this->namespaces = $namespaces;
        $this->fields = $fields;
    }

    public function constructorSignature() : string
    {
        $signature = '    public function __construct(';
        $params = [];
        foreach($this->fields as $name => $type)
        {
            $params[] = "{$type} \${$name}";
        }
        $signature .= join(', ', $params);
        $signature .= ')';

        return $signature;
    }

    public function fieldList() : string
    {
        $fields = [];
        foreach($this->fields as $name => $type)
        {
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