<?php

namespace Info\Relations;

use SimpleXMLElement;
use Throwable;

class ManyToMany extends AbstractRelation
{
    /** @var null|JoinTable $joinTable */
    private ?JoinTable $joinTable = null;

    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        try {
            parent::__construct($simpleXmlElement);
            $simpleXmlElement = (array) $simpleXmlElement;

            if (array_key_exists("join-table", $simpleXmlElement)) {
                $this->joinTable = new JoinTable($simpleXmlElement["join-table"]);
            }
        } catch (Throwable $exception) {
            print_r(compact('simpleXMLElement', 'exception'));
            die();
        }
    }

    public function __toString(): string
    {
        $joinTableSerialized = "";
        if ($this->joinTable) {
            $joinTableSerialized = "
            * {$this->joinTable->serializeAnnotation()}
            ";
        }

        return "/**
     * @var {$this->getValueType()} \${$this->name}
     * {$this->serializeAnnotation()}{$joinTableSerialized}
     */";
    }

    /**
     * @inheritDoc
     */
    protected function getValueType(): string
    {
        return "{$this->targetEntity}[]";
    }
}
