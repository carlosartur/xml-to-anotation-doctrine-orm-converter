<?php

namespace Info;

use Closure;
use ReflectionClass;

trait AnnotationAttributesTrait
{
    /**
     * This array must have class attribute names that will not be included on final annotation serialization.
     *
     * @var array
     */
    public $paramsNotIncluded = [];

    /**
     * This array must have class attribute arrays that will be included as json strings
     *
     * @var array
     */
    public $paramsJsonArray = [];

    /**
     * This array has the custom functions to get correct format of a value
     *
     * @var Closure[]
     */
    public $customCallbackToGetValue = [];

    /**
     * Serialize a object using annotation parameters, like example above:
     *
     * class Example
     * {
     *     use AnnotationAttributesTrait;
     *     private string $attrString;
     *     private bool $attrBool;
     *     private float $attrFloat;
     *     private string $attrNotIncluded;
     *     public function __construct($attrString, $attrBool, $attrFloat, $attrNotIncluded)
     *     {
     *         $this->paramsNotIncluded = ['attrNotIncluded'];
     *         $this->attrString = $attrString;
     *         $this->attrBool = $attrBool;
     *         $this->attrFloat = $attrFloat;
     *         $this->attrNotIncluded = $attrNotIncluded;
     *     }
     * }
     * $example = new Example('foo', true, 3.14, 'not included');
     * echo $example; // prints 'attrString="foo", attrBool=true, attrFloat=3.14'
     *
     * @return string
     */
    public function getValuesString(): string
    {
        $extraOptsString = [];
        foreach ($this as $attribute => $value) {
            if (
                in_array($attribute, $this->paramsJsonArray)
                && is_array($value)
                && count($value)
            ) {
                $extraOptsString[] = "{$attribute}=" . json_encode($value);
                continue;
            }

            if (array_key_exists($attribute, $this->customCallbackToGetValue)) {
                /** @var Closure */
                $closure = $this->customCallbackToGetValue[$attribute];

                $resultOfClosure = $closure($value);
                if ($resultOfClosure) {
                    $extraOptString = "{$attribute}=" . $closure($value);
                    $extraOptsString[] = $extraOptString;
                }
                continue;
            }

            if (
                in_array($attribute, $this->paramsNotIncluded)
                || !is_scalar($value)
            ) {
                continue;
            }

            $value = $this->getValueString($value);

            if (is_null($value)) {
                continue;
            }

            $extraOptsString[] = "{$attribute}={$value}";
        }
        return implode(', ', $extraOptsString);
    }

    /**
     * Get annotation valid value to attribute
     *
     * @param scalar $value
     * @return string
     */
    public function getValueString($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return "\"{$value}\"";
    }

    /**
     * Get class name for annotation.
     *
     * @return string
     */
    public function getAnnotationClassName(): string
    {
        $reflect = new ReflectionClass($this);
        $classShortName = $reflect->getShortName();
        return "@ORM\\{$classShortName}";
    }

    public function serializeAnnotation(): string
    {
        return "{$this->getAnnotationClassName()}({$this->getValuesString()})";
    }

    public function __toString(): string
    {
        return $this->serializeAnnotation();
    }
}
