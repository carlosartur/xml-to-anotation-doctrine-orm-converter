<?php

namespace Info\Relations;

use Info\AnnotationAttributesTrait;
use Info\Fields\FieldInfo;
use Info\Fields\JoinColumn;
use SimpleXMLElement;

abstract class AbstractRelation extends FieldInfo
{
    use AnnotationAttributesTrait;

    /** @var string $targetEntity */
    protected ?string $targetEntity;

    /** @var string $inversedBy */
    protected ?string $inversedBy;

    /** @var JoinColumn|null $joinColumn */
    protected ?JoinColumn $joinColumn = null;

    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        $simpleXmlElement = (array) $simpleXmlElement;
        $attributes = $simpleXmlElement['@attributes'];

        $this->name = $attributes['field'] ?? null;
        $this->targetEntity = $attributes['target-entity'] ?? null;
        $this->inversedBy = $attributes['inversed-by'] ?? null;
        $this->fetch = $attributes['fetch'] ?? null;

        if (isset($simpleXmlElement['join-column'])) {
            $this->joinColumn = new JoinColumn($simpleXmlElement['join-column']);
        }
    }

    /**
     * Put all null attributes on blacklist array to not include on annotation
     *
     * @return void
     */
    private function buildParamsNotIncludedArray(): void
    {
        $this->paramsNotIncluded = [
            "type",
            "nullable",
            "unique",
            "precision",
            "scale",
        ];

        foreach ($this as $attr => $value) {
            if (is_null($value)) {
                $this->paramsNotIncluded[] = $attr;
            }
        }
    }

    public function __toString(): string
    {
        if (!$this->joinColumn) {
            return "/**
     * {$this->serializeAnnotation()}
     */";
        }

        return "/**
     * {$this->serializeAnnotation()}
     * {$this->joinColumn->serializeAnnotation()}
     */";
    }
}
