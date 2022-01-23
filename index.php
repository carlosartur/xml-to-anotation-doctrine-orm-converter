#!/usr/bin/php
<?php
require 'autoload.php';

use Main\Standardizer;
use Main\Output;

try {
    $configs = json_decode(file_get_contents(__DIR__ . "/config.json"));
    $path = $argv[1] ?? '';
    $standizer = new Standardizer($path, $configs);
    $standizer->startStandardization();
} catch (Exception $exception) {
    Output::error($exception->getMessage());
}
