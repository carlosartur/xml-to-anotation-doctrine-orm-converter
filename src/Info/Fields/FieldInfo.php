<?php

namespace Info\Fields;

use Info\AnnotationAttributesTrait;
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

    /** @var array $options */
    protected array $options = [];

    public function __construct(SimpleXMLElement $simpleXMLElement)
    {
        $this->paramsJsonArray[] = "options";
        
        $fieldInfoContainer = (array) $simpleXMLElement;
        $fieldInfo = $fieldInfoContainer['@attributes'];
        $fieldOptions = array_key_exists("options", $fieldInfoContainer)
            ? $simpleXMLElement->options
            : null;

        if ($fieldOptions) {
            $this->fillOptions($fieldOptions);
        }

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

    /**
     * Use options on xml to build options to field.
     * Unfortunately, SimpleXMLElement::attributes doesn't work here, so it uses
     * string logic to do that.
     *
     * @param SimpleXMLElement $fieldOptions
     * @return void
     */
    public function fillOptions(SimpleXMLElement $fieldOptions)
    {
        $optionsString = str_replace(
            ['<options>', '</options>'],
            '',
            $fieldOptions->asXML()
        );

        $options = array_filter(
            explode('<option ', $optionsString),
            fn ($item) => (bool) strlen(trim($item))
        );

        foreach ($options as $option) {
            $option = str_replace('</option>', '', $option);
            $keyValue = explode(">", $option);
            $value = trim(array_pop($keyValue));
            $key = str_replace(['name=', '"'], '', array_shift($keyValue));

            $value = json_decode($value);
            $this->options[$key] = $value;
        }
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
