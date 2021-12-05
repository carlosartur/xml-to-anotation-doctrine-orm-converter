<?php

namespace Info\Relations;

use Info\AnnotationAttributesTrait;
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
