<?php

namespace Info\Fields;

use SimpleXMLElement;

class FunctionInfo
{
    /** @var string|null */
    private ?string $type;

    /** @var string|null */
    private ?string $method;

    public function __construct(SimpleXMLElement $simpleXMLElement)
    {
        $info = (array) $simpleXMLElement;
        $info = $info['@attributes'];

        $this->type = $info['type'] ?? null;
        $this->method = $info['method'] ?? null;
    }

    /**
     * Get regex to define if class has a function
     *
     * @return string
     */
    public function getAccessibleFunctionRegex(): string
    {
        return "/(public|protected)\\s{0,}function\\s{0,}{$this->method}\\s/";
    }

    /**
     * Get regex to get docs of this method
     *
     * @return string
     */
    public function getDocRegex(): string
    {
        return '#\/\*\*(((.)+\n{0,})|(\n){0,}(.+\n))\s{0,}\*\/(?=\n\s{0,}((public|protected)\s{0,}function\s{0,}'
            . $this->method
            . '\W))#';
    }

    /**
     * Get regex to find if type already exists on docs of method
     *
     * @return string
     */
    public function getTypeRegex(): string
    {
        $type = ucfirst($this->type);
        return '/\@ORM\\' . $type;
    }

    /**
     * Get type orm information
     *
     * @return string
     */
    public function getType(): string
    {
        $type = ucfirst($this->type);
        return '@ORM\\' . $type;
    }
}
