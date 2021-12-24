<?php

namespace Info\Relations;

use Info\AnnotationAttributesTrait;
use Info\EntityOrmInfo;
use Info\Fields\JoinColumn;
use SimpleXMLElement;

class JoinTable
{
    use AnnotationAttributesTrait;

    /** @var JoinColumn[] */
    protected array $joinColumnCollection = [];

    /** @var JoinColumn[] */
    protected array $reversedJoinColumnCollection = [];

    /** @var string|null */
    protected ?string $joinColumns;

    /** @var string|null */
    protected ?string $reversedJoinColumns;

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
}
