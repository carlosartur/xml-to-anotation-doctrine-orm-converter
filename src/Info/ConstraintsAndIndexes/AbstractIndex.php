<?php

namespace Info\ConstraintsAndIndexes;

use ReflectionClass;
use SimpleXMLElement;

abstract class AbstractIndex
{
    /** @var string $name */
    protected string $name;

    /** @var string $columns */
    protected string $columns;

    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        $simpleXmlElement = (array) $simpleXmlElement;
        $attributes = $simpleXmlElement['@attributes'];

        $this->name = $attributes['name'] ?? null;
        $this->columns = $attributes['columns'] ?? null;
    }

    public function __toString(): string
    {
        $columns = str_replace(",", '","', $this->columns);
        $shortName = (new ReflectionClass($this))->getShortName();
        return "@{$shortName}(name=\"{$this->name}\",columns={\"{$columns}\"})";
    }
}
