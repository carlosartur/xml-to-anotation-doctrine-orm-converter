<?php

namespace Info;

use SimpleXMLElement;

class FieldInfo
{
    /** @var string $name */
    protected string $name;

    /** @var string $column */
    protected string $column;

    /** @var string $type */
    protected string $type = 'string';

    /** @var int $length */
    protected ?int $length;

    /** @var bool $nullable */
    protected bool $nullable = true;

    /** @var bool $unique */
    protected bool $unique = false;

    /** @var bool $precision */
    protected int $precision = 0;

    /** @var int $scale */
    protected int $scale = 0;

    public function __construct(SimpleXMLElement $simpleXMLElement)
    {
        $fieldInfo = (array) $simpleXMLElement;
        $fieldInfo = $fieldInfo['@attributes'];

        $this->name = $fieldInfo['name'];
        $this->column = $fieldInfo['column'];
        $this->type = $fieldInfo['type'] ?? 'string';
        $this->scale = $fieldInfo['scale'] ?? 0;
        $this->unique = $fieldInfo['unique'] ?? false;
        $this->precision = $fieldInfo['precision'] ?? 0;
        $this->length = isset($fieldInfo['length'])
            ? (int) $fieldInfo['length']
            : null;

        $this->nullable = isset($fieldInfo['nullable'])
            ? filter_var($fieldInfo['nullable'], FILTER_VALIDATE_BOOL)
            : true;
    }

    public function __toString(): string
    {
        $length = $this->length ? ", length={$this->length}" : '';
        $nullable = $this->nullable ? "true" : "false";
        $scale = $this->scale ? ", scale={$this->scale}" : '';
        $unique = $this->unique ? ", unique=true" : '';
        $precision = $this->precision ? ", precision={$this->precision}" : '';
        return "/**
     * @ORM\Column(name=\"{$this->column}\", type=\"{$this->type}\", nullable={$nullable}{$length}{$scale}{$unique}{$precision})
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
