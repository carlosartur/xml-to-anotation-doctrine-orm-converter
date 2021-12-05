<?php

namespace Info\Fields;

use Info\AnnotationAttributesTrait;
use SimpleXMLElement;

class JoinColumn
{
    use AnnotationAttributesTrait;

    /** @var string $referencedColumnName */
    private ?string $referencedColumnName;

    /** @var string $referencedColumnName */
    private ?bool $nullable;

    /** @var string $onDelete */
    private ?string $onDelete;

    /** @var string $onDelete */
    private ?string $onUpdate;

    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        $simpleXmlElement = (array) $simpleXmlElement;
        $attributes = $simpleXmlElement['@attributes'];

        $this->name = $attributes['name'] ?? null;
        $this->referencedColumnName = $attributes['referenced-column-name'] ?? null;
        $this->nullable = filter_var($attributes['nullable'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        $this->onDelete = $attributes['on-delete'] ?? null;
        $this->onUpdate = $attributes['on-update'] ?? null;
    }
}
