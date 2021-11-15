<?php

namespace Info;

use SimpleXMLElement;

abstract class AbstractRelation extends FieldInfo
{
    /** @var string $targetEntity */
    protected string $targetEntity;

    /** @var string $inversedBy */
    protected string $inversedBy;

    /** @var JoinColumn|null $joinColumn */
    protected ?JoinColumn $joinColumn;

    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        $simpleXmlElement = (array) $simpleXmlElement;
        $attributes = $simpleXmlElement['@attributes'];

        $this->name = $attributes['field'] ?? null;
        $this->targetEntity = $attributes['target-entity'] ?? null;
        $this->inversedBy = $attributes['inversed-by'] ?? null;
        $this->fetch = $attributes['fetch'] ?? null;

        $this->joinColumn = new JoinColumn($simpleXmlElement['join-column']);
    }
}
