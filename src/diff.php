<?php

namespace Differ\diff;

use Tightenco\Collect;
use Symfony\Component\Yaml\Yaml;

use function Differ\Formatters\pretty\renderPretty;
use function Differ\Formatters\pretty\renderPretty2;
use function Differ\Formatters\plain\renderPlain;

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
    $format = $result->args['--format'];
    print_r(getDiff($firstFile, $secondFile, $format));
}

function getDiff($firstFilePath, $secondFilePath, $format = "pretty")
{
    $firstValues = parse($firstFilePath);
    $secondValues = parse($secondFilePath);

    //$unionValues = array_replace_recursive($firstValues, $secondValues);

   // $dataWithAction = diffTree($firstValues, $secondValues);
    $objTree = diffObjTree($firstValues, $secondValues);

    switch ($format) {
        case 'plain':
            $result = renderPlain($objTree);
            break;
        default:
            $result = renderPretty($objTree);
            break;
    }
    
    
    
    //$testStdfunc = Collection\flattenAll($testStd);

    //print_r("\nFirst config\n");
    //print_r($objTree);
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
    
    //return $merge;
    //$diffFirst = diffTree($firstNode, $secondNode);
    //print_r("\nFirst collect\n");
    //print_r($firstNode);
    //print_r("\second collect\n");
    //print_r($secondNode);
    //print_r("\Diff\n");
    //print_r($merge);
    //print_r("\With Action\n");
    //print_r($dataWithAction);

    return $tree;
}

function diffObjTree($firstNode, $secondNode)
{
    $collectionFirst = collect($firstNode);
    $collectionSecond = collect($secondNode);
    $mapped = function ($firstTree, $secondTree) use (&$mapped) {
        $firstMapped = $firstTree->map(function ($item, $key) use ($secondTree, &$mapped) {
            if ($secondTree->has($key)) {
                $secondValue = $secondTree->get($key);
                $status = $item == $secondValue ? "unchanged" : "changed";
            } else {
                $status = "deleted";
                $secondValue = null;
            }
            $oldValue = $item;
            $newValue = $secondValue;
            return is_object($item) && is_object($secondValue) ? collect([
                'status' => $status,
                'key' => $key,
                'oldValue' => $oldValue,
                'newValue' => $newValue,
                'children' => $mapped(collect($item), collect($secondValue)),
            ]) :
            collect([
                'status' => $status,
                'key' => $key,
                'oldValue' => $oldValue,
                'newValue' => $newValue,
                'children' => null
            ]);
        });

        $addedKeys = $secondTree->diffKeys($firstTree)
        ->map(function ($item, $key) {
            return collect([
                'status' => 'added',
                'key' => $key,
                'oldValue' => null,
                'newValue' => $item,
                'children' => null
            ]);
        });
        $result = $addedKeys->isNotEmpty() ? $firstMapped->merge($addedKeys) : $firstMapped;
        return $result;
    };
    return collect($mapped($collectionFirst, $collectionSecond));
}
//print_r(diffTree(parse("../tests/fixtures/before.json"), parse("../tests/fixtures/after.json"), "plain"));
//getdiff('../tests/fixtures/before.json', '../tests/fixtures/after.json');
