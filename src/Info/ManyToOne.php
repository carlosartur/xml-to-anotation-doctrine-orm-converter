<?php

namespace Info;

use SimpleXMLElement;

class ManyToOne extends AbstractRelation
{
    use AnnotationAttributesTrait;

    public function __toString(): string
    {
        return "/**
     * {$this->serializeAnnotation()}
     * {$this->joinColumn->serializeAnnotation()}
     */";
    }
}
