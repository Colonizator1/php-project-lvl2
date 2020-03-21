<?php

namespace Differ\diff;

use Tightenco\Collect;
use Symfony\Component\Yaml\Yaml;

use function Differ\Formatters\pretty\renderPretty;

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
    $firstValues = parse($firstFilePath);
    $secondValues = parse($secondFilePath);

    //$unionValues = array_replace_recursive($firstValues, $secondValues);

    $dataWithAction = diffTree($firstValues, $secondValues);

    switch ($options['--format']) {
        case 'plain':
            $result = renderPlain($dataWithAction);
            break;
        default:
            $result = renderPretty($dataWithAction);
            break;
    }
    
    
    //$testStdfunc = Collection\flattenAll($testStd);

    //print_r("\nFirst config\n");
    print_r($dataWithAction);

    $result = "{\n" . $result . "\n}\n";
    if ($options) {
        print_r($result);
    }
    return $result;
}
function parse($path)
{
    try {
        $path_parts = pathinfo($path);
        $file = file_get_contents($path);
        if ($path_parts['extension'] == 'json') {
            return json_decode($file);
        } elseif ($path_parts['extension'] == 'yaml') {
            return Yaml::parse($file, Yaml::PARSE_OBJECT_FOR_MAP);
        }
        throw new \Exception("File doesn't have json or yaml extention");
    } catch (\Exception $e) {
        echo $e->getMessage(), "\n";
    }
}

function diffTree($firstNode, $secondNode)
{
    /*
    $collectionFirst = collect($firstNode);
    $collectionSecond = collect($secondNode);

    $deleteKeys = $collectionFirst->diffKeys($collectionSecond);
    $addKeys = $collectionSecond->diffKeys($collectionFirst);

    $merge = $collectionFirst->mergeRecursive($collectionSecond);
*/
    $tree = [];
    foreach ($firstNode as $key => $value) {
        if (!property_exists($secondNode, $key)) {
            $status = "delete";
            $secondValue = null;
        } else {
            $secondValue = $secondNode->$key;
            if ($value == $secondValue) {
                $status = "unchanged";
            } else {
                $status = "changed";
            }
        }
        $oldValue = $value;
        $newValue = $secondValue;
        $children = is_object($value) && is_object($secondValue) ? diffTree($value, $secondValue) : false;
        $tree[] = [
            'status' => $status,
            'key' => $key,
            'oldValue' => $oldValue,
            'newValue' => $newValue,
            'children' => $children
        ];
    }
    foreach ($secondNode as $key => $value) {
        if (!property_exists($firstNode, $key)) {
            $tree[] = [
                'status' => "add",
                'key' => $key,
                'oldValue' => null,
                'newValue' => $value,
                'children' => false
            ];
        }
    }
    return $tree;
    //$diffFirst = diffTree($firstNode, $secondNode);
    //print_r("\nFirst collect\n");
    //print_r($firstNode);
    //print_r("\second collect\n");
    //print_r($secondNode);
    //print_r("\Diff\n");
    //print_r($diffFirst);
    //print_r("\With Action\n");
    //print_r($dataWithAction);
}


//print_r(diffTree(parse("../tests/fixtures/before.json"), parse("../tests/fixtures/after.json")));
//getdiff('../tests/fixtures/before.json', '../tests/fixtures/after.json');
