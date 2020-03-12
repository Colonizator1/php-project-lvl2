<?php

namespace Differ;

use Tightenco\Collect;

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
function parse($path)
{
    try {
        $path_parts = pathinfo($path);
        $file = file_get_contents($path);
        if ($path_parts['extension'] == 'json') {
            return json_decode($file);
        } elseif ($path_parts['extension'] == 'yml') {
            return;
        }
        throw new \Exception("File doesn't have json or yml extention");
    } catch (\Exception $e) {
        echo $e->getMessage(), "\n";
    }
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
    print_r($dataWithAction);

    
    if ($options) {
        print_r($result);
    }
    return $result;
}

function getStrFromBool($bool)
{
    return $bool ? 'true' : 'false';
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
    $diffFirst = diffTree($firstNode, $secondNode);
    print_r("\nFirst collect\n");
    print_r($firstNode);
    print_r("\second collect\n");
    print_r($secondNode);
    print_r("\Diff\n");
    print_r($diffFirst);
    //print_r("\With Action\n");
    //print_r($dataWithAction);
}
function getStrFromNode($collection, $level = 0)
{
    if (is_array($collection)) {
        $openBreacket = '[';
        $closeBreacket = ']';
    }
    if (is_object($collection)) {
        $openBreacket = '{';
        $closeBreacket = '}';
    }
    $spaces = str_repeat("  ", $level);
    $result = [];
    $result[] = "{$spaces}$openBreacket";
    foreach ($collection as $value) {
        $result[] = is_array($value) || is_object($value)
        ? getStrFromNode($value, ++$level)
        : "{$spaces}  " . getStr($value);
    }
    $result[] = "{$spaces}$closeBreacket";

    return implode("\n", $result);
}

function getStr($value)
{
    switch (gettype($value)) {
        case 'boolean':
            return $value ? 'true' : 'false';
            break;
        case 'NULL':
            return "null";
            break;
        case 'array':
            return getStrFromNode($value);
            break;
        case 'object':
            return getStrFromNode($value);
            break;
        default:
            return "$value";
            break;
    }
}

function render($tree, $level = 1) {
    $spaces = str_repeat("  ", $level);
    $result = array_reduce($tree, function ($acc, $node) use ($spaces, $level) {
        if ($node['children']) {
            $acc[] = "$spaces{";
            $acc[] = render($node['children'], $level++);
            $acc[] = "$spaces}";
        } else {
            if ($node['status'] == 'add') {
                $acc[] = "{$spaces}+ {$node['key']}: " . getStr($node['newValue']) . "\n";
            } elseif ($node['status'] == 'delete') {
                $acc[] = "{$spaces}- {$node['key']}: " . getStr($node['oldValue']) . "\n";
            } elseif ($node['status'] == 'unchanged') {
                $acc[] = "{$spaces}  {$node['key']}: " . getStr($node['oldValue']) . "\n";
            } else {
                $acc[] = "{$spaces}- {$node['key']}: " . getStr($node['oldValue']) . "\n";
                $acc[] = "{$spaces}+ {$node['key']}: " . getStr($node['newValue']) . "\n";
            }
        }
    }, []);
    return "{\n" . implode("\n", $result) . "\n}";
}
function renderDiff($tree, $spaceCol = 4)
{
    return array_reduce($tree, function ($acc, $value) use ($spaceCol) {
        $spaces = str_repeat(" ", $spaceCol);
        if ($value['type'] == 'array') {
            $openBreacket = '[';
            $closeBreacket = ']';
        }
        if ($value['type'] == 'assoc') {
            $openBreacket = '{';
            $closeBreacket = '}';
        }
        if ($value['type'] == 'string') {
            if ($value['status'] == 'add') {
                $acc .= "{$spaces}+ {$value['key']}: {$value['value']}\n";
            } elseif ($value['status'] == 'del') {
                $acc .= "{$spaces}- {$value['key']}: {$value['value']}\n";
            } elseif ($value['status'] == 'unchanged') {
                $acc .= "{$spaces}  {$value['key']}: {$value['value']}\n";
            } else {
                $acc .= "{$spaces}- {$value['key']}: {$value['old_value']}\n";
                $acc .= "{$spaces}+ {$value['key']}: {$value['value']}\n";
            }
        } elseif ($value['type'] == 'array' || $value['type'] == 'assoc') {
            if ($value['status'] == 'del') {
                $acc .= "{$spaces}- {$value['key']}: $openBreacket\n";
                $acc .= renderDiff($value['value'], $spaceCol * 2);
                $acc .= "{$spaces}  $closeBreacket\n";
            } elseif ($value['status'] == 'add') {
                $acc .= "{$spaces}+ {$value['key']}: $openBreacket\n";
                $acc .= renderDiff($value['value'], $spaceCol * 2);
                $acc .= "{$spaces}  $closeBreacket\n";
            } else {
                $acc .= "{$spaces}  {$value['key']}: $openBreacket\n";
                $acc .= renderDiff($value['value'], $spaceCol * 2);
                $acc .= "{$spaces}  $closeBreacket\n";
            }
        }
        return $acc;
    }, '');
}

function buildDiff($tree, $firstNode, $secondNode)
{
    $values = array_map(function ($key, $value) use ($tree, $firstNode, $secondNode) {
        $type = 'string';
        if (!array_key_exists($key, $firstNode)) {
            if (is_array($secondNode[$key])) {
                $type = isset($secondNode[$key][0]) ? 'array' : 'assoc';
                return [
                    'status' => 'add',
                    'type' => $type,
                    'key' => "$key",
                    'value' => buildDiff($value, $tree[$key], $secondNode[$key])
                ];
            }
            return [
                'status' => 'add',
                'type' => $type,
                'key' => "$key",
                'value' => is_bool($value) ? getStrFromBool($value) : $value
            ];
        } elseif (!array_key_exists($key, $secondNode)) {
            if (is_array($firstNode[$key])) {
                $type = isset($firstNode[$key][0]) ? 'array' : 'assoc';
                return [
                    'status' => 'del',
                    'type' => $type,
                    'key' => "$key",
                    'value' => buildDiff($value, $firstNode[$key], $tree[$key])
                ];
            }
            return [
                'status' => 'del',
                'type' => $type,
                'key' => "$key",
                'value' => is_bool($value) ? getStrFromBool($value) : $value
            ];
        } elseif ($firstNode[$key] == $secondNode[$key]) {
            if (is_array($value)) {
                $type = isset($value[0]) ? 'array' : 'assoc';
                return [
                    'status' => 'unchanged',
                    'type' => $type,
                    'key' => "$key",
                    'value' => buildDiff($value, $firstNode[$key], $secondNode[$key])
                ];
            }
            return [
                'status' => 'unchanged',
                'type' => $type,
                'key' => "$key",
                'value' => is_bool($value) ? getStrFromBool($value) : $value
            ];
        } else {
            if (is_array($firstNode[$key]) && is_array($secondNode[$key])) {
                $type = isset($value[0]) ? 'array' : 'assoc';
                return [
                    'status' => 'change',
                    'type' => $type,
                    'key' => "$key",
                    'value' => buildDiff($value, $firstNode[$key], $secondNode[$key])
                ];
            }
            return [
                'status' => 'change',
                'type' => $type,
                'key' => "$key",
                'old_value' => $firstNode[$key],
                'value' => is_bool($value) ? getStrFromBool($value) : $value
            ];
        }
    }, array_keys($tree), $tree);
    return $values;
}

//print_r(diffTree(parse("../tests/fixtures/before.json"), parse("../tests/fixtures/after.json")));
//getdiff('../tests/fixtures/before.json', '../tests/fixtures/after.json');
