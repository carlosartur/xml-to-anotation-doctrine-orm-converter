<?php

namespace Info;

use DOMDocument;
use Info\ConstraintsAndIndexes\Index;
use Info\ConstraintsAndIndexes\UniqueConstraint;
use Info\Fields\FieldInfo;
use Info\Fields\FunctionInfo;
use Info\Fields\IdInfo;
use Info\Relations\ManyToMany;
use Info\Relations\ManyToOne;
use Info\Relations\OneToMany;
use Main\CodeFile;

class EntityOrmInfo
{
    /** @var string $entityClassName */
    private string $entityClassName;

    /** @var string $table */
    private string $table;

    /** @var string $repositoryClass */
    private string $repositoryClass;

    /** @var bool $hasLifecycleCallbacks */
    private bool $hasLifecycleCallbacks = false;

    /** @var FieldInfo[] */
    private array $fields = [];

    /** @var CodeFile|null */
    private ?CodeFile $codeFile = null;

    /** @var FunctionInfo[] */
    private array $functionsInfos = [];

    /** @var Index[] */
    private array $indexes = [];

    /** @var UniqueConstraint[] */
    private array $uniqueConstraints = [];

    public function __construct(string $xml)
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $this->buildInformation($dom);
    }

    /**
     *
     *
     * @param DOMDocument $dom
     * @return void
     */
    public function buildInformation(DOMDocument $dom)
    {
        $entityInfo = simplexml_import_dom($dom);
        $entityInfo = (array) $entityInfo->entity;

        $this->setEntityClassName($entityInfo['@attributes']['name'])
            ->setTable($entityInfo['@attributes']['table'])
            ->setRepositoryClass($entityInfo['@attributes']['repository-class'])
            ->setHasLifecycleCallbacks($entityInfo['lifecycle-callbacks'] ?? null);

        foreach (self::getElementsArray($entityInfo, 'indexes') as $indexes) {
            foreach (self::getElementsArray((array) $indexes, 'index') as $index) {
                $index = new Index($index);
                $this->addIndex($index);
            }
        }

        foreach (self::getElementsArray($entityInfo, 'unique-constraints') as $UniqueConstraint) {
            foreach (self::getElementsArray((array) $UniqueConstraint, 'unique-constraint') as $uniqueConstraint) {
                $uniqueConstraint = new UniqueConstraint($uniqueConstraint);
                $this->addUniqueConstraint($uniqueConstraint);
            }
        }

        if ($this->getHasLifecycleCallbacks()) {
            foreach ($entityInfo['lifecycle-callbacks'] as $lifecycleCallback) {
                $functionInfo = new FunctionInfo($lifecycleCallback);
                $this->addFunctionInfo($functionInfo);
            }
        }

        foreach (self::getElementsArray($entityInfo, 'field') as $field) {
            $fieldInfo = new FieldInfo($field);
            $this->addField($fieldInfo);
        }

        foreach (self::getElementsArray($entityInfo, 'many-to-one') as $field) {
            $fieldInfo = new ManyToOne($field);
            $this->addField($fieldInfo);
        }

        foreach (self::getElementsArray($entityInfo, 'one-to-many') as $field) {
            $fieldInfo = new OneToMany($field);
            $this->addField($fieldInfo);
        }

        foreach (self::getElementsArray($entityInfo, 'many-to-many') as $field) {
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
    public static function getElementsArray(array $entityInfo, string $name): array
    {
        if (!array_key_exists($name, $entityInfo)) {
            return [];
        }

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
     * Add a field info to the value of fields
     *
     * @return  self
     */
    public function addField(FieldInfo $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Get the value of Indexes
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * Add a Index info to the value of Indexes
     *
     * @return  self
     */
    public function addIndex(Index $index)
    {
        $this->indexes[] = $index;

        return $this;
    }

    /**
     * Get the value of uniqueConstraints
     */
    public function getUniqueConstraints()
    {
        return $this->uniqueConstraints;
    }

    /**
     * Add a UniqueConstraint info to the value of UniqueConstraints
     *
     * @return  self
     */
    public function addUniqueConstraint(UniqueConstraint $uniqueConstraint)
    {
        $this->uniqueConstraints[] = $uniqueConstraint;

        return $this;
    }

    /**
     * Add function info
     *
     * @return  self
     */
    public function addFunctionInfo(FunctionInfo $functionInfo)
    {
        $this->functionsInfos[] = $functionInfo;

        return $this;
    }

    /**
     * Get function info
     *
     * @return  array
     */
    public function getFunctionsInfos(): array
    {
        return $this->functionsInfos;
    }

    public function __toString()
    {
        $tableExtraInfo = "";
        if ($this->getUniqueConstraints()) {
            $uniqueConstraintsString = [];
            foreach ($this->getUniqueConstraints() as $uniqueConstraint) {
                $uniqueConstraintsString[] = (string) $uniqueConstraint;
            }
            $tableExtraInfo .= ",
            *    uniqueConstraints={"
                . implode(",", $uniqueConstraintsString)
                . ")}";
        }

        if ($this->getIndexes()) {
            $indexesString = [];
            foreach ($this->getIndexes() as $index) {
                $indexesString[] = (string) $index;
            }
            $tableExtraInfo .= ",
            *    indexes={"
                . implode(",", $indexesString)
                . ")}";
        }

        return "/**
 * @ORM\Entity
 * @ORM\Table(name=\"{$this->table}\"{$tableExtraInfo})
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

    /**
     * Get the value of hasLifecycleCallbacks
     */
    public function getHasLifecycleCallbacks()
    {
        return $this->hasLifecycleCallbacks;
    }

    /**
     * Set the value of hasLifecycleCallbacks
     *
     * @return  self
     */
    public function setHasLifecycleCallbacks($hasLifecycleCallbacks)
    {
        $this->hasLifecycleCallbacks = (bool) $hasLifecycleCallbacks;

        return $this;
    }

    /**
     * Get the value of codeFile
     */
    public function getCodeFile(): ?CodeFile
    {
        return $this->codeFile;
    }

    /**
     * Set the value of codeFile
     *
     * @return  self
     */
    public function setCodeFile(?CodeFile $codeFile)
    {
        $this->codeFile = $codeFile;

        return $this;
    }
}
