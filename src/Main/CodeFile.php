<?php

namespace Main;

class CodeFile
{
    /** @var boolean */
    private bool $isClassOrTrait = false;

    /** @var string */
    private string $namespace;

    /** @var string */
    private string $className;

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
            return $this;
        }

        $declarationClassLineParts = explode(' extends ', $this->declarationClassLine);
        $extendsLineParts = explode(' ', array_pop($declarationClassLineParts));

        $fatherClass = array_shift($extendsLineParts);
        $this->fatherClass = $this->imports[$fatherClass];
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
}
