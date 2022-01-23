<?php

namespace Main;

use JsonSerializable;

class CodeFile implements JsonSerializable
{
    /** @var boolean */
    private bool $isClassOrTrait = false;

    /** @var string */
    private ?string $namespace = "\\";

    /** @var string */
    private ?string $className = "";

    /** @var string */
    private ?string $declarationClassLine = null;

    /** @var string[] */
    private array $imports = [];

    /** @var string[] */
    private array $traits = [];

    /** @var string[] */
    private ?array $codeLines;

    /** @var string|null */
    private ?string $fatherClass;

    /** @var string|null */
    private ?string $fullQualifiedClassName = null;

    /** @var int */
    private int $classDeclarationLineNumber = 0;

    /** @var CodeFile|null */
    private ?CodeFile $fatherClassCode = null;

    /** @var CodeFile[] */
    private array $traitsClassCode = [];

    public function __construct(
        private string $path
    ) {
        $fileContent = file_get_contents($path);
        $this->codeLines = explode(PHP_EOL, $fileContent);

        $this->configureIsClassOrTrait();

        if ($this->isClassOrTrait) {
            $this->configureNamespace($fileContent)
                ->configureImports()
                ->configureFatherClass()
                ->configureClassName();

            $this->getFullQualifiedClassName();
        }
        $this->codeLines = null;
    }

    /**
     * Get all imports of class, to find father class or used traits
     *
     * @return self
     */
    private function configureImports(): self
    {
        foreach ($this->codeLines as $lineNumber => $codeLine) {
            $matches = [];
            preg_match('/(?<=((use)\s)).+(?=;)/', $codeLine, $matches);

            if (count($matches)) {
                $import = array_shift($matches);
                if (false === stripos($import, '\\')) {
                    $import = "{$this->namespace}\\$import";
                }

                $importName = explode('\\', $import);
                $importName = array_pop($importName);

                if (false !== stripos($importName, ' as ')) {
                    $importName = explode(' as ', $import);
                    $importName = array_pop($importName);
                }

                if ($lineNumber > $this->classDeclarationLineNumber) {
                    $this->traits[$importName] = $import;
                }

                $this->imports[$importName] = $import;
            }
        }
        return $this;
    }

    /**
     * Define if code file is a class or a trait
     *
     * @return void
     */
    private function configureIsClassOrTrait(): self
    {
        foreach ($this->codeLines as $lineNumber => $codeLine) {
            $matches = [];
            preg_match('/(?<=((class)\s)).+/', $codeLine, $matches);
            if (count($matches)) {
                $this->isClassOrTrait = true;
            }

            if (!$this->isClassOrTrait) {
                preg_match('/(?<=((trait)\s)).+/', $codeLine, $matches);
                if (count($matches)) {
                    $this->isClassOrTrait = true;
                }
            }

            if ($this->isClassOrTrait) {
                $this->declarationClassLine = array_shift($matches);
                $this->classDeclarationLineNumber = $lineNumber;
                return $this;
            }
        }

        return $this;
    }

    /**
     * Configure namespace of class
     *
     * @param [type] $fileContent
     * @return self
     */
    private function configureNamespace(string $fileContent): self
    {
        $matches = [];
        preg_match('/(?<=((namespace)\s)).+(?=;)/', $fileContent, $matches);
        $this->namespace = array_shift($matches);
        return $this;
    }

    /**
     * Get father class if exists
     *
     * @param [type] $fileContent
     * @return self
     */
    private function configureFatherClass(): self
    {
        if (
            !$this->isClassOrTrait
            || false === stripos($this->declarationClassLine, ' extends ')
        ) {
            $this->fatherClass = null;
            return $this;
        }

        $declarationClassLineParts = explode(' extends ', $this->declarationClassLine);
        $extendsLineParts = explode(' ', array_pop($declarationClassLineParts));

        $fatherClass = array_shift($extendsLineParts);
        $fatherClass = $this->imports[$fatherClass] ?? "{$this->namespace}\\{$fatherClass}";

        $fatherClass = explode(' ', $fatherClass);
        $this->fatherClass = array_shift($fatherClass);

        return $this;
    }

    /**
     * @return string
     */
    public function getFullQualifiedClassName(): string
    {
        if (!$this->fullQualifiedClassName) {
            $this->fullQualifiedClassName = "{$this->namespace}\\{$this->className}";
        }
        return $this->fullQualifiedClassName;
    }

    /**
     * Get class or trait name
     *
     * @return self
     */
    private function configureClassName(): self
    {
        if (!$this->isClassOrTrait) {
            return $this;
        }

        $declarationClassLineParts = explode(' ', $this->declarationClassLine);
        $this->className = array_shift($declarationClassLineParts);
        return $this;
    }

    /**
     * Configure father class to get code. 
     *
     * @param array $listOfClasses
     * @return self
     */
    public function configureFatherClassCode(array $listOfClasses): self
    {
        $this->fatherClassCode = $listOfClasses[$this->fatherClass] ?? null;
        return $this;
    }

    /**
     * Get code of father class. 
     *
     * @param array $listOfClasses
     * @return void
     */
    public function getFatherClassCode(): ?CodeFile
    {
        if (!$this->fatherClassCode) {
            return null;
        }
        return $this->fatherClassCode;
    }

    /**
     * Configure the list of code of traits
     *
     * @param array $listOfClasses
     * @return self
     */
    public function configureTraitsCodes(array $listOfClasses): self
    {
        foreach ($this->traits as $trait) {
            $this->traitsClassCode[$trait] = $listOfClasses[$trait];
        }
        if ($this->traits) {
            var_dump([$this->traits, $this->traitsClassCode]);
            echo json_encode($listOfClasses, JSON_PRETTY_PRINT);
            exit();
        }
        return $this;
    }

    /**
     * Get the value of isClassOrTrait
     */
    public function getIsClassOrTrait(): bool
    {
        return $this->isClassOrTrait;
    }

    /**
     * Set the value of isClassOrTrait
     *
     * @return  self
     */
    public function setIsClassOrTrait(bool $isClassOrTrait)
    {
        $this->isClassOrTrait = $isClassOrTrait;

        return $this;
    }

    /**
     * Get the value of imports
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * Set the value of imports
     *
     * @return  self
     */
    public function setImports($imports)
    {
        $this->imports = $imports;

        return $this;
    }

    /**
     * Get the value of path
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Set the value of path
     *
     * @return  self
     */
    public function setPath(?string $path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the value of traits
     */
    public function getTraits()
    {
        return $this->traits;
    }

    /**
     * Set the value of traits
     *
     * @return  self
     */
    public function setTraits($traits)
    {
        $this->traits = $traits;

        return $this;
    }

    /**
     * Get the value of fatherClass
     */
    public function getFatherClass()
    {
        return $this->fatherClass;
    }

    /**
     * Set the value of fatherClass
     *
     * @return  self
     */
    public function setFatherClass($fatherClass)
    {
        $this->fatherClass = $fatherClass;

        return $this;
    }

    /**
     * Get code of the file from filesystem
     *
     * @return string
     */
    public function readCode(): string
    {
        return file_get_contents($this->getPath());
    }

    /**
     * Write code of the file to filesystem
     *
     * @return string
     */
    public function writeCode(string $entityCode): void
    {
        file_put_contents($this->getPath(), $entityCode);
    }

    public function jsonSerialize()
    {
        $data = [];
        foreach ($this as $prop => $value) {
            $data[$prop] = $value;
        }
        return $data;
    }
}
