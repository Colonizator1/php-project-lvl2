<?php

namespace Differ;

function startDiff()
{
    $doc = <<<DOC
Generate diff

Usage:
    gendiff (-h|--help)
    gendiff (-v|--version)
    gendiff [--format <fmt>] <firstFile> <secondFile>

Options:
    -h --help                     Show this screen
    -v --version                  Show version
    --format <fmt>                Report format [default: pretty]
DOC;
    $result = \Docopt::handle($doc, array('version' => '1.0', 'firstFile', 'secondFile'));
    $firstFile = $result->args['<firstFile>'];
    $secondFile = $result->args['<secondFile>'];
    genDiff($firstFile, $secondFile, $result->args);
}

function genDiff($firstFilePath, $secondFilePath, $options = [])
{
    $result = "{\n";
    try {
        if (file_get_contents($firstFilePath, true) === false) {
            throw new \Exception("Can't read the first file!");
        } elseif (file_get_contents($secondFilePath, true) === false) {
            throw new \Exception("Can't read the second file!");
        }
    } catch (\Exception $e) {
        echo 'Exception was thrown: ',  $e->getMessage(), "\n";
    }
    $firstFile = file_get_contents($firstFilePath);
    $secondFile = file_get_contents($secondFilePath);

    $firstValues = json_decode($firstFile, true);
    $secondValues = json_decode($secondFile, true);
    $unionValues = array_merge($firstValues, $secondValues);
    foreach ($unionValues as $key => $value) {
        if (!array_key_exists($key, $firstValues)) {
            $result .= "  - $key: $value\n";
        } elseif ($firstValues[$key] === $value) {
            $result .= "    $key: $value\n";
        } elseif ($firstValues[$key] !== $value) {
            $result .= "  + $key: $value\n";
            $result .= "  - $key: $firstValues[$key]\n";
        }
    }
    $result .= "}\n";
    /*
    print_r("\nFirst config\n");
    print_r($firstValues);
    print_r("\nSecond config\n");
    print_r($secondValues);
    print_r("\nUnion conf\n");
    print_r($union);
    print_r("\nDocopt parse options\n");
    print_r($options);
    print_r("\nResult\n");
    print_r($result);
    */
    return $result;
}
