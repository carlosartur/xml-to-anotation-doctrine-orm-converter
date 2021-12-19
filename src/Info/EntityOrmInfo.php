<?php

namespace Info;

use DOMDocument;
use Info\Fields\FieldInfo;
use Info\Fields\IdInfo;
use Info\Relations\ManyToMany;
use Info\Relations\ManyToOne;
use Info\Relations\OneToMany;
use Main\Output;
use SimpleXMLElement;

class EntityOrmInfo
{
    /** @var string $entityClassName */
    private string $entityClassName;

    /** @var string $table */
    private string $table;

    /** @var string $repositoryClass */
    private string $repositoryClass;

    /** @var FieldInfo[] */
    private array $fields = [];

    public function __construct(string $xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $this->buildInformation($dom);
    }

    public function buildInformation(DOMDocument $dom)
    {
        $entityInfo = simplexml_import_dom($dom);
        $entityInfo = (array) $entityInfo->entity;
        $this->setEntityClassName($entityInfo['@attributes']['name'])
            ->setTable($entityInfo['@attributes']['table'])
            ->setRepositoryClass($entityInfo['@attributes']['repository-class']);

        foreach ($this->getElementsArray($entityInfo, 'field') as $field) {
            $fieldInfo = new FieldInfo($field);
            $this->addField($fieldInfo);
        }

        foreach ($this->getElementsArray($entityInfo, 'many-to-one') as $field) {
            $fieldInfo = new ManyToOne($field);
            $this->addField($fieldInfo);
        }

        foreach ($this->getElementsArray($entityInfo, 'one-to-many') as $field) {
            $fieldInfo = new OneToMany($field);
            $this->addField($fieldInfo);
        }

        foreach ($this->getElementsArray($entityInfo, 'many-to-many') as $field) {
            $fieldInfo = new ManyToMany($field);
            $this->addField($fieldInfo);
        }

        if ($entityInfo['id']) {
            $this->addField(new IdInfo($entityInfo['id']));
        }
    }

    /**
     * Get element list, or a array with only one element if only one element is find
     *
     * @param array $entityInfo
     * @param string $name
     * @return array
     */
    private function getElementsArray(array $entityInfo, string $name): array
    {
        if (!is_array($entityInfo[$name])) {
            return [$entityInfo[$name]];
        }
        return $entityInfo[$name];
    }

    /**
     * Get the value of name
     */
    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setEntityClassName(string $entityClassName): self
    {
        $this->entityClassName = $entityClassName;

        return $this;
    }

    /**
     * Get the value of table
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set the value of table
     *
     * @return  self
     */
    public function setTable(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get the value of repositoryClass
     */
    public function getRepositoryClass(): string
    {
        return $this->repositoryClass;
    }

    /**
     * Set the value of repositoryClass
     *
     * @return  self
     */
    public function setRepositoryClass(string $repositoryClass): self
    {
        $this->repositoryClass = $repositoryClass;

        return $this;
    }

    /**
     * Get the value of fields
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set the value of fields
     *
     * @return  self
     */
    public function addField(FieldInfo $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    public function __toString()
    {
        return "/**
 * @ORM\Entity
 * @ORM\Table(name=\"{$this->table}\")
 * @ORM\Entity(repositoryClass=\"{$this->repositoryClass}\")
 */";
    }

    public function getFilePath()
    {
        $namespace = str_replace('\\', '/', $this->entityClassName);
        return "src/{$namespace}.php";
    }

    public static function getClassDocRegex()
    {
        return '#\/\*\*(((.)+\n{0,})|(\n){0,}(.+\n))\s{0,}\*\/(?=\n\s{0,}((class\s+([a-zA-Z])+)))#';
    }

    public static function getClassRegex()
    {
        return '#(class\s+([a-zA-Z])+)#';
    }
}
