<?php

namespace Info;

use SimpleXMLElement;

class FieldInfo
{
    use AnnotationAttributesTrait;

    /** @var string $name */
    protected string $name;

    /** @var string $column */
    protected string $column;

    /** @var string $type */
    protected string $type = 'string';

    /** @var int $length */
    protected ?int $length;

    /** @var bool $nullable */
    protected ?bool $nullable = true;

    /** @var bool $unique */
    protected ?bool $unique = false;

    /** @var int $precision */
    protected ?int $precision = 0;

    /** @var int $scale */
    protected ?int $scale = 0;

    public function __construct(SimpleXMLElement $simpleXMLElement)
    {
        $fieldInfo = (array) $simpleXMLElement;
        $fieldInfo = $fieldInfo['@attributes'];

        $this->name = $fieldInfo['name'];
        $this->column = $fieldInfo['column'];
        $this->type = $fieldInfo['type'] ?? null;
        $this->scale = $fieldInfo['scale'] ?? null;
        $this->precision = $fieldInfo['precision'] ?? null;

        $this->length = isset($fieldInfo['length'])
            ? (int) $fieldInfo['length']
            : null;

        $this->unique = isset($fieldInfo['unique'])
            ? filter_var($fieldInfo['unique'], FILTER_VALIDATE_BOOL)
            : null;

        $this->nullable = isset($fieldInfo['nullable'])
            ? filter_var($fieldInfo['nullable'], FILTER_VALIDATE_BOOL)
            : null;
    }

    public function getAnnotationClassName(): string
    {
        return "@ORM\Column";
    }

    public function __toString(): string
    {
        return "/**
     * {$this->serializeAnnotation()}
     */";
    }

    public function getPropertyDocRegex(): string
    {
        return '#\/\*\*(((.)+\n{0,})|(\n){0,}(.+\n))\s{0,}\*\/(?=\n\s+((public|protected|private)\s\$' . $this->name . '\W))#';
    }

    public function getPropertyRegex(): string
    {
        return '#\n{1,}(?=((public|protected|private)\s\$' . $this->name . '\W))#';
    }

    public function buildClassProperty(): string
    {
        return "\n    " . $this . "\n    private \$" . $this->getName() . ';';
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
