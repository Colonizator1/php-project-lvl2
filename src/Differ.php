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
    getDiff($firstFile, $secondFile, $result->args);
}

function getDiff($firstFilePath, $secondFilePath, $options = [])
{
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
    
    $unionStr = array_map(function ($key, $value) {
        return is_bool($value) ? getStrFromBool($value) : $value;
    }, array_keys($unionValues), $unionValues);
    $unionResult = array_combine(array_keys($unionValues), $unionStr);

    $result = [];
    foreach ($unionResult as $key => $value) {
        if (!array_key_exists($key, $firstValues)) {
            $result[] = "\n  + $key: $value";
        } elseif (!array_key_exists($key, $secondValues)) {
            $result[] = "\n  - $key: $value";
        } elseif ($firstValues[$key] === $secondValues[$key]) {
            $result[] = "\n    $key: $value";
        } else {
            $result[] = "\n  + $key: $secondValues[$key]";
            $result[] = "\n  - $key: $firstValues[$key]";
        }
    }
    $strResult = "{" . implode('', $result) . "\n}\n";
    /*
    print_r("\nFirst config\n");
    print_r($firstValues);
    print_r("\nSecond config\n");
    print_r($secondValues);
    print_r("\nUnion conf\n");
    print_r($unionValues);
    print_r("\nDocopt parse options\n");
    print_r($options);
    print_r("\nResult\n");
    */
    if ($options) {
        print_r($strResult);
    }
    return $strResult;
}

function getStrFromBool($bool)
{
    return $bool ? 'true' : 'false';
}
