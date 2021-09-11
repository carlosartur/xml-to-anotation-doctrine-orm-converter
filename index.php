#!/usr/bin/php
<?php
require 'autoload.php';

use Main\Standardizer;
use Main\Output;

try {
    $path = $argv[1] ?? '';
    $standizer = new Standardizer($path);
    $standizer->startStandardization();
} catch (Exception $exception) {
    Output::error($exception->getMessage());
}
