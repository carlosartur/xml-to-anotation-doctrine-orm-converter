<?php

namespace Info\Relations;

use SimpleXMLElement;

class ManyToMany extends AbstractRelation
{
    public function __construct(SimpleXMLElement $simpleXmlElement)
    {
        parent::__construct($simpleXmlElement);
        $simpleXmlElement = (array) $simpleXmlElement;
    }
}
