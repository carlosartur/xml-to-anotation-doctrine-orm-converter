<?php

namespace Main;

use Exception;
use Info\EntityOrmInfo;
use Info\Fields\FunctionInfo;
use stdClass;

class Standardizer
{
    private const DOC_START = "/**";

    /** @var string[] */
    private array $ormFiles;

    /** @var string[] */
    private array $classFiles;

    /**
     * Undocumented variable
     *
     * @var array
     */
    private array $classesList;

    public function __construct(
        /** @var string $path - Path to project that you want to put on standards */
        private string $path = '',
        /** @var array|null $config - Configurations of project */
        private ?stdClass $config = null,
    ) {
        if (!strlen($this->path)) {
            throw new Exception("You must pass the path of the project you want to put on standards.");
        }

        if (is_file($this->path)) {
            throw new Exception("Path \"{$this->path}\" option must be a directory, file path given.");
        }

        if (!is_dir($this->path)) {
            throw new Exception("Path \"{$this->path}\" not found.");
        }

        $this->buildTraitsAndClassesList();
        $this->buildTraitsAndClassesCodeList();
        $this->buildXmlOrmFileList();
    }

    /**
     * Build list of 'xml' files that should be readen by this software.
     *
     * @param string|null $currentPath
     * @return void
     */
    private function buildXmlOrmFileList(?string $currentPath = null)
    {
        $currentPath = $currentPath ?? $this->path;
        foreach (scandir($currentPath) as $subpath) {
            if (in_array($subpath, ['.', '..'])) {
                continue;
            }

            $completeSubPath = "{$currentPath}/{$subpath}";
            if (is_dir($completeSubPath)) {
                $this->buildXmlOrmFileList($completeSubPath);
                continue;
            }

            if (false === stripos($completeSubPath, '.orm.xml')) {
                continue;
            }
            $this->ormFiles[] = $completeSubPath;
        }
    }

    /**
     * Build list of 'php' files that should be readen by this software.
     *
     * @param string|null $currentPath
     * @return void
     */
    private function buildTraitsAndClassesList(?string $currentPath = null)
    {
        $currentPath = $currentPath ?? $this->path;
        foreach (scandir($currentPath) as $subpath) {
            if (in_array($subpath, ['.', '..'])) {
                continue;
            }

            $completeSubPath = "{$currentPath}/{$subpath}";
            if (is_dir($completeSubPath)) {
                $this->buildTraitsAndClassesList($completeSubPath);
                continue;
            }

            $fileExtension = explode('.', $completeSubPath);
            $fileExtension = array_pop($fileExtension);

            if ('php' !== $fileExtension) {
                continue;
            }

            $classFile = new CodeFile($completeSubPath);
            $this->classFiles[$classFile->getFullQualifiedClassName()] = $classFile;
        }
    }

    /**
     * Get code read by buildTraitsAndClassesList function to create tree of traits and classes
     *
     * @see self::buildTraitsAndClassesList
     *
     * @return void
     */
    private function buildTraitsAndClassesCodeList()
    {
        $this->classFiles = array_filter(
            $this->classFiles,
            function (CodeFile $codeFile) {
                if (!$codeFile->getIsClassOrTrait()) {
                    return false;
                }

                foreach ($this->getIgnoredNamespaces() as $ignoredNamespace) {
                    if (0 === stripos($codeFile->getFullQualifiedClassName(), $ignoredNamespace)) {
                        return false;
                    }
                }

                return true;
            }
        );

        Logger::getInstance()->info($this->classFiles);

        /** @var CodeFile $classFile */
        foreach ($this->classFiles as $classFile) {
            $classFile->configureTraitsCodes($this->classFiles)
                ->configureFatherClassCode($this->classFiles);
        }
    }

    /**
     * Executes Standardization
     *
     * @return void
     */
    public function startStandardization()
    {
        $totalFiles = count($this->ormFiles);
        foreach ($this->ormFiles as $key => $ormFile) {
            try {
                $numberFile = $key + 1;

                $message = "File #{$numberFile} of {$totalFiles} file name: {$ormFile}";
                Output::info($message);
                Logger::getInstance()->info($message);

                $fileContent = file_get_contents($ormFile);
                $entityOrmInfo = new EntityOrmInfo($fileContent);

                /** @var CodeFile $codeFile */
                $codeFile = $this->classFiles[$entityOrmInfo->getEntityClassName()];

                $entityOrmInfo->setCodeFile($codeFile);

                $entityCode = $codeFile->readCode();

                Logger::getInstance()->info("updateFunctionAttributes");
                $entityCode = $this->updateFunctionAttributes($entityOrmInfo, $entityCode);

                Logger::getInstance()->info("updateFields");
                $entityCode = $this->updateFields($entityOrmInfo, $entityCode);

                Logger::getInstance()->info("updateClassAttributes");
                $entityCode = $this->updateClassAttributes($entityOrmInfo, $entityCode);

                $codeFile->writeCode($entityCode);

                $message = "{$ormFile} success!";
                Output::success($message);
                Logger::getInstance()->info($message);
            } catch(Exception $exception) {
                $message = "{$ormFile} error!" . PHP_EOL . print_r($exception, true);
                Output::error($message);
                Logger::getInstance()->critical(print_r($exception));
            }
        }
    }

    /**
     * Add orm info to entity class annotation
     *
     * @param EntityOrmInfo $entityOrmInfo
     * @param string $entityCode
     * @return string
     */
    private function updateClassAttributes(EntityOrmInfo $entityOrmInfo, string $entityCode): string
    {
        if (preg_match(EntityOrmInfo::getClassDocRegex(), $entityCode)) {
            return preg_replace(EntityOrmInfo::getClassDocRegex(), (string) $entityOrmInfo, $entityCode);
        }
        return preg_replace(EntityOrmInfo::getClassRegex(), $entityOrmInfo . PHP_EOL . '$0', $entityCode);
    }

    /**
     * Add orm info to entity fields annotation
     *
     * @param EntityOrmInfo $entityOrmInfo
     * @param string $entityCode
     * @return string
     */
    private function updateFields(EntityOrmInfo $entityOrmInfo, string $entityCode): string
    {
        foreach ($entityOrmInfo->getFields() as $field) {
            if (preg_match($field->getPropertyDocRegex(), $entityCode)) {
                $entityCode = preg_replace($field->getPropertyDocRegex(), (string) $field, $entityCode);
                continue;
            }

            if (preg_match($field->getPropertyRegex(), $entityCode)) {
                $entityCode = preg_replace($field->getPropertyRegex(), $field->buildClassProperty(), $entityCode);
                continue;
            }

            $entityCodeLineByLine = explode(PHP_EOL, $entityCode);
            $bestLineToInsertProperty = $this->findFirstDisponibleLineForProperty($entityCodeLineByLine);

            $entityPieces = array_chunk($entityCodeLineByLine, $bestLineToInsertProperty);
            $entityFirstPiece = array_shift($entityPieces);

            $entityFooter = implode(PHP_EOL, array_map(fn ($item) => implode(PHP_EOL, $item), $entityPieces));

            $entityCode = implode(PHP_EOL, $entityFirstPiece) . PHP_EOL
                . $field->buildClassProperty() . PHP_EOL
                . $entityFooter;
        }
        return $entityCode;
    }

    /**
     * Add info to annotations of function
     *
     * @param EntityOrmInfo $entityOrmInfo
     * @param string $entityCode
     * @return string
     */
    private function updateFunctionAttributes(EntityOrmInfo $entityOrmInfo, string $entityCode): string
    {
        $codeFile = $entityOrmInfo->getCodeFile();
        /** @var FunctionInfo $functionInfo */
        foreach ($entityOrmInfo->getFunctionsInfos() as $functionInfo) {
            $entityCode = $this->entityClassFunctionInfo($functionInfo, $codeFile, $entityCode);
        }
        return $entityCode;
    }

    /**
     * Update function to add info about
     *
     * @return void
     */
    private function entityClassFunctionInfo(
        FunctionInfo $functionInfo,
        CodeFile $codeFile,
        string $entityCode
    ): string {
        if (
            preg_match_all($functionInfo->getAccessibleFunctionRegex(), $entityCode)
            && $this->writeFunctionCallbackInfo($functionInfo, $codeFile)
        ) {
            return $codeFile->readCode();
        }

        /** @var CodeFile $traitClassCode */
        foreach ($codeFile->getTraitsClassCode() as $traitClassCode) {
            if ($this->writeFunctionCallbackInfo($functionInfo, $traitClassCode)) {
                return $codeFile->readCode();
            }
        }

        if (
            $codeFile->getFatherClassCode()
            && $this->entityParentClassFunctionInfo($functionInfo, $codeFile->getFatherClassCode())
        ) {
            return $codeFile->readCode();
        }
        return $entityCode;
    }

    /**
     *
     *
     * @param FunctionInfo $functionInfo
     * @param CodeFile $codeFile
     * @return void
     */
    private function entityParentClassFunctionInfo(FunctionInfo $functionInfo, CodeFile $codeFile): bool
    {
        if ($this->writeFunctionCallbackInfo($functionInfo, $codeFile)) {
            return true;
        }

        if (!$codeFile->getFatherClassCode()) {
            return false;
        }

        return $this->entityParentClassFunctionInfo($functionInfo, $codeFile->getFatherClassCode());
    }

    /**
     *
     *
     * @param FunctionInfo $functionInfo
     * @param CodeFile $codeFile
     * @return void
     */
    private function writeFunctionCallbackInfo(FunctionInfo $functionInfo, CodeFile $codeFile): bool
    {
        $classCode = $codeFile->readCode();
        $functionDeclarationMatches = [];
        if (!preg_match_all($functionInfo->getAccessibleFunctionRegex(), $classCode, $functionDeclarationMatches)) {
            return false;
        }

        $functionDocMatches = [];
        $numberOfMatches = preg_match($functionInfo->getDocRegex(), $classCode, $functionDocMatches);
        if (!$numberOfMatches) {
            $functionDeclarationLine = str_replace(PHP_EOL, '', $functionDeclarationMatches[0][0]);
            $functionDoc = "/**
     * {$functionInfo->getType()}
     */";
            $functionDeclarationLineWithDoc = $functionDoc . PHP_EOL . $functionDeclarationLine;
            $newclassCode = str_replace($functionDeclarationLine, $functionDeclarationLineWithDoc, $classCode);

            $codeFile->writeCode($newclassCode);
            return true;
        }

        $functionDoc = array_shift($functionDocMatches);
        if (preg_match($functionInfo->getTypeRegex(), $functionDoc)) {
            return true;
        }

        $functionDocLines = explode(PHP_EOL, $functionDoc);
        do {
            $docPiece = array_shift($functionDocLines);
        } while (false === stripos($docPiece, self::DOC_START) && count($functionDocLines));

        if (!count($functionDocLines)) {
            return false;
        }

        $indentation = str_repeat(" ", strlen($functionDocLines[0]) - strlen(ltrim($functionDocLines[0])));
        $functionFinalDoc = self::DOC_START
            . PHP_EOL
            . "{$indentation}* {$functionInfo->getType()}"
            . PHP_EOL
            . implode(PHP_EOL, $functionDocLines);

        $finalClassCode = preg_replace(
            $functionInfo->getDocRegex(),
            $functionFinalDoc,
            $classCode,
            1
        );
        $codeFile->writeCode($finalClassCode);

        return true;
    }


    /**
     * Find line to add property.
     *
     * @param array $entityCodeLineByLine
     * @return integer
     */
    private function findFirstDisponibleLineForProperty(array $entityCodeLineByLine): int
    {
        $lineForProperty = 0;
        $isClassDefinitionLineFound = false;
        foreach ($entityCodeLineByLine as $lineNumber => $line) {
            if (preg_match(EntityOrmInfo::getClassRegex(), $line)) {
                $isClassDefinitionLineFound = true;
                $lineForProperty = $lineNumber + 2;
            }

            if ($isClassDefinitionLineFound && preg_match('#(use\s+[a-zA-Z\\\d]+)\;#', $line)) {
                $lineForProperty = $lineNumber + 1;
            }
        }

        return $lineForProperty;
    }

    /**
     * Get ignored namespaces on configurations
     *
     * @return array
     */
    public function getIgnoredNamespaces(): array
    {
        return $this->config?->namespaces_to_ignore ?? [];
    }
}
