<?php

namespace Info;

use SimpleXMLElement;

class IdInfo extends FieldInfo
{
    private $generator;

    public function __construct(SimpleXMLElement $simpleXMLElement)
    {
        parent::__construct($simpleXMLElement);
        $generator = (array) $simpleXMLElement->generator;
        $generator = $generator['@attributes'];
        $this->generator = (object) $generator;
    }

    public function __toString()
    {
        return "    /**
     * @ORM\Column(type=\"integer\")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy=\"{$this->generator->strategy}\")
     */";
    }
}
