<?php

namespace Info;

use SimpleXMLElement;

class LifecycleCallback
{
    /** @var string */
    private string $type;

    /** @var string */
    private string $method;

    public function __construct(SimpleXMLElement $simpleXMLElement)
    {
        $simpleXMLElement = (array) $simpleXMLElement;

        $attributes = $simpleXMLElement['@attributes'];
        
        $this->setType($attributes['type'])
            ->setMethod($attributes['method']);
    }

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the value of method
     *
     * @return  self
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }
}
