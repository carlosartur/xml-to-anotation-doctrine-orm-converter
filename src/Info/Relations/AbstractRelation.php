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

    /** @var string[] $cascade */
    protected array $cascade = [];

    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        $this->buildParamsNotIncludedArray();

        $simpleXmlElement = (array) $simpleXmlElement;
        $attributes = $simpleXmlElement['@attributes'];

        $this->name = $attributes['field'] ?? null;
        $this->targetEntity = $attributes['target-entity'] ?? null;
        $this->inversedBy = $attributes['inversed-by'] ?? null;
        $this->fetch = $attributes['fetch'] ?? null;

        if (isset($simpleXmlElement['join-column'])) {
            $this->joinColumn = new JoinColumn($simpleXmlElement['join-column']);
        }

        if (isset($simpleXmlElement['cascade'])) {
            $this->buildCascades($simpleXmlElement['cascade']);
        }

        $this->customCallbackToGetValue['cascade'] = function (array $value): string {
            if (!count($value)) {
                return "";
            }
            return str_replace(
                ["[", "]"],
                ["{", "}"],
                json_encode($value)
            );
        };
    }

    /**
     * Build cascade for entity
     *
     * @param SimpleXMLElement $simpleXmlElement
     * @return void
     */
    private function buildCascades(SimpleXMLElement $simpleXmlElement): void
    {
        foreach (array_keys((array)$simpleXmlElement) as $cascade) {
            $this->cascade[] = str_replace("cascade-", "", $cascade);
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
