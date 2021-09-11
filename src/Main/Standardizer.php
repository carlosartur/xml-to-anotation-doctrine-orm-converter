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

            file_put_contents("{$this->path}/{$entityOrmInfo->getFilePath()}", $entityCode);
        }
    }

    public function updateFields(EntityOrmInfo $entityOrmInfo, string $entityCode): string
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

            $pattern = '#(\n{1,}(?=(\s+(public|protected|private)\s(\w|\$)+)))#';
            if (preg_match($pattern, $entityCode)) {
                [$classDefinition, $functionsDefinition] = preg_split($pattern, $entityCode, 2);
                $entityCode = "{$classDefinition}{$field->buildClassProperty()}{$functionsDefinition}";
                continue;
            }
        }
        return $entityCode;
    }
}
