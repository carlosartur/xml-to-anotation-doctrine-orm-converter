<?php

namespace Info\Relations;

use SimpleXMLElement;

class ManyToMany extends AbstractRelation
{
    /** @var JoinTable $joinTable */
    private JoinTable $joinTable;

    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        parent::__construct($simpleXmlElement);
        $simpleXmlElement = (array) $simpleXmlElement;

        $this->joinTable = new JoinTable($simpleXmlElement["join-table"]);

        echo $this;
    }

    public function __toString(): string
    {
        return "/**
     * {$this->serializeAnnotation()}
     * {$this->joinTable->serializeAnnotation()}
     */";
    }
}
