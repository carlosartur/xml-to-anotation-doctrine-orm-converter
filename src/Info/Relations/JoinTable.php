<?php

namespace Info\Relations;

use Info\AnnotationAttributesTrait;
use Info\EntityOrmInfo;
use Info\Fields\JoinColumn;
use SimpleXMLElement;

class JoinTable
{
    use AnnotationAttributesTrait {
        AnnotationAttributesTrait::getValueString as getValueStringScapingString;
    }

    /** @var JoinColumn[] */
    protected array $joinColumnCollection = [];

    /** @var JoinColumn[] */
    protected array $reversedJoinColumnCollection = [];

    /** @var string|null */
    protected ?string $joinColumns;

    /** @var string|null */
    protected ?string $reversedJoinColumns;

    /** @var string */
    protected string $name;

    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        $simpleXmlElement = (array) $simpleXmlElement;

        $this->name = $simpleXmlElement["@attributes"]["name"];

        $joinColumns = EntityOrmInfo::getElementsArray((array) $simpleXmlElement["join-columns"], "join-column");
        foreach ($joinColumns as $joinColumn) {
            $this->joinColumnCollection[] = new JoinColumn($joinColumn);
        }

        $reversedJoinColumns = EntityOrmInfo::getElementsArray(
            (array) $simpleXmlElement["inverse-join-columns"],
            "join-column"
        );
        foreach ($reversedJoinColumns as $joinColumn) {
            $this->reversedJoinColumnCollection[] = new JoinColumn($joinColumn);
        }

        $this->buildJoins();
    }

    /**
     * Build information of joins
     *
     * @return void
     */
    private function buildJoins(): void
    {
        $joinColumns = [];
        /** @var JoinColumn $joinColumn */
        foreach ($this->joinColumnCollection as $joinColumn) {
            $joinColumns[] = $joinColumn->serializeAnnotation();
        }
        $joinColumns = implode(",", $joinColumns);
        $this->joinColumns = "{{$joinColumns}}";

        $reversedJoinColumns = [];
        /** @var JoinColumn $joinColumn */
        foreach ($this->reversedJoinColumnCollection as $joinColumn) {
            $reversedJoinColumns[] = $joinColumn->serializeAnnotation();
        }
        $joinColumns = implode(",", $reversedJoinColumns);
        $this->reversedJoinColumns = "{{$joinColumns}}";
    }

    /**
     * Get annotation valid value to attribute
     *
     * @param [type] $value
     * @return string|null
     */
    public function getValueString($value): ?string
    {
        if (
            is_string($value)
            && false !== stripos($value, '{@ORM\\')
        ) {
            return "{$value}";
        }

        return $this->getValueStringScapingString($value);
    }
}
