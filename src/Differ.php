<?php

namespace Differ;

use Tightenco\Collect;
use Symfony\Component\Yaml\Yaml;

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
    $result = render($dataWithAction);
    
    //$testStdfunc = Collection\flattenAll($testStd);

    //print_r("\nFirst config\n");
    //print_r($dataWithAction);

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

function render($tree, $level = 1)
{
    $spacesCol = 2;
    $spacesCol = $spacesCol * ($level + $level - 1);
    $space = str_repeat(" ", $spacesCol);
    $result = array_reduce($tree, function ($acc, $node) use ($space, $level) {
        if (!$node['children']) {
            if ($node['status'] == 'add') {
                $acc[] = "{$space}+ {$node['key']}: " . getStr($node['newValue'], $level);
            } elseif ($node['status'] == 'delete') {
                $acc[] = "{$space}- {$node['key']}: " . getStr($node['oldValue'], $level);
            } elseif ($node['status'] == 'unchanged') {
                $acc[] = "{$space}  {$node['key']}: " . getStr($node['oldValue'], $level);
            } else {
                $acc[] = "{$space}- {$node['key']}: " . getStr($node['oldValue'], $level);
                $acc[] = "{$space}+ {$node['key']}: " . getStr($node['newValue'], $level);
            }
        } else {
            switch ($node['status']) {
                case 'add':
                    $sign = '+';
                    break;
                case 'delete':
                    $sign = '-';
                    break;
                case 'unchanged':
                    $sign = ' ';
                    break;
                case 'changed':
                    $sign = ' ';
                    break;
            }
            $acc[] = "$space$sign {$node['key']}: {";
            $acc[] = render($node['children'], ++$level);
            $acc[] = "$space}";
        }
        return $acc;
    }, []);
    return implode("\n", $result);
}

function getStrFromNode($collection, $level = 0)
{
    $spacesCol = 2;
    $spacesCol = $spacesCol * ($level + $level - 1);
    $space = str_repeat(" ", $spacesCol);
    $spaceBeforeCloseBracket = str_repeat(" ", $spacesCol - 2);
    $nodeData = [];

    if (is_array($collection)) {
        $openBreacket = '[';
        $closeBreacket = ']';
        $nodeData[] = "$openBreacket";
        ++$level;
        foreach ($collection as $value) {
            $nodeData[] = is_array($value) || is_object($value)
            ? "{$space}  " . getStrFromNode($value, $level)
            : "{$space}  " . getStr($value);
        }
        $level++;
        $nodeData[] = "{$spaceBeforeCloseBracket}$closeBreacket";
    }
    if (is_object($collection)) {
        $openBreacket = '{';
        $closeBreacket = '}';
        $nodeData[] = "$openBreacket";
        ++$level;
        foreach ($collection as $key => $value) {
            $nodeData[] = is_array($value) || is_object($value)
            ? "{$space}  $key: " . getStrFromNode($value, $level)
            : "{$space}  $key: " . getStr($value, $level);
        }
        $nodeData[] = "{$spaceBeforeCloseBracket}$closeBreacket";
    }
    return implode("\n", $nodeData);
}

function getStr($value, $level = 0)
{
    switch (gettype($value)) {
        case 'boolean':
            return $value ? 'true' : 'false';
            break;
        case 'NULL':
            return "null";
            break;
        case 'array':
            return getStrFromNode($value, ++$level);
            break;
        case 'object':
            return getStrFromNode($value, ++$level);
            break;
        default:
            return "$value";
            break;
    }
}
//print_r(diffTree(parse("../tests/fixtures/before.json"), parse("../tests/fixtures/after.json")));
//getdiff('../tests/fixtures/before.json', '../tests/fixtures/after.json');
