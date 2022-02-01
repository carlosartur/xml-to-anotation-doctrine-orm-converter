<?php

namespace Info\Relations;

class OneToMany extends AbstractRelation
{
    /**
     * @inheritDoc
     */
    protected function getValueType(): string
    {
        return "{$this->targetEntity}[]";
    }
}
