<?php

namespace Main;

use Exception;
use Info\EntityOrmInfo;

class Standardizer
{
    /** @var string[] */
    private array $ormFiles;

    public function __construct(
        /** @var string $path - Path to project that you want to put on standards */
        private string $path = '',
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
        $this->buildFileList();
    }

    private function buildFileList(?string $currentPath = null)
    {
        $currentPath = $currentPath ?? $this->path;
        foreach (scandir($currentPath) as $subpath) {
            if (in_array($subpath, ['.', '..'])) {
                continue;
            }

            $completeSubPath = "{$currentPath}/{$subpath}";
            if (is_dir($completeSubPath)) {
                $this->buildFileList($completeSubPath);
                continue;
            }

            if (false === stripos($completeSubPath, '.orm.xml')) {
                continue;
            }
            $this->ormFiles[] = $completeSubPath;
        }
    }

    public function startStandardization()
    {
        foreach ($this->ormFiles as $ormFile) {
            Output::info("Starting standardization for file {$ormFile}");
            $fileContent = file_get_contents($ormFile);
            $entityOrmInfo = new EntityOrmInfo($fileContent);

            $entityCode = file_get_contents("{$this->path}/{$entityOrmInfo->getFilePath()}");

            $entityCode = $this->updateFields($entityOrmInfo, $entityCode);

            $entityCode = $this->updateClassAttributes($entityOrmInfo, $entityCode);

            file_put_contents("{$this->path}/{$entityOrmInfo->getFilePath()}", $entityCode);
        }
    }

    private function updateClassAttributes(EntityOrmInfo $entityOrmInfo, string $entityCode): string
    {
        if (preg_match(EntityOrmInfo::getClassDocRegex(), $entityCode)) {
            return preg_replace(EntityOrmInfo::getClassDocRegex(), (string) $entityOrmInfo, $entityCode);
        }
        return preg_replace(EntityOrmInfo::getClassRegex(), $entityOrmInfo . PHP_EOL . '$0', $entityCode);
    }

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
}
