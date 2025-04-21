<?php

declare(strict_types=1);

namespace thomas\phplox\ast_gen;

class TypeDefinitionCollection
{
    /** @var array<TypeDefinition> */
    public array $typeDefinitions = [];
    public string $baseTypeName = "";

    /** @param array<TypeDefinition> $typeDefinitions */
    public function __construct(string $baseTypeName, array $typeDefinitions = [])
    {
        $this->baseTypeName = $baseTypeName;
        $this->typeDefinitions = $typeDefinitions;
    }
}