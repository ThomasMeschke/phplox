<?php

declare(strict_types=1);

namespace thomas\phplox\ast_gen;

class PropertyDefinition
{
    public string $name = "";
    public string $typeName = "";
    public ?string $stanTypeName = null;

    public function __construct(string $name, string $typeName, ?string $stanTypeName = null)
    {
        $this->name = $name;
        $this->typeName = $typeName;
        $this->stanTypeName = $stanTypeName;
    }
}