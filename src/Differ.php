<?php

namespace Differ;

use Tightenco\Collect;

use function Differ\Parser\parse;
use function Differ\Formatters\Pretty\renderPretty;
use function Differ\Formatters\Plain\renderPlain;
use function Differ\Formatters\Json\renderJson;

function getDiff($firstFilePath, $secondFilePath, $format = "pretty")
{
    $firstFileContent = file_get_contents($firstFilePath);
    $secondFileContent = file_get_contents($secondFilePath);
    $firstFilePathParts = pathinfo($firstFilePath);
    $secondFilePathParts = pathinfo($secondFilePath);
    if ($firstFileContent === false) {
        throw new \Exception("Can't read file by: {$firstFilePath}");
    }
    if ($secondFileContent === false) {
        throw new \Exception("Can't read file by: {$secondFilePath}");
    }
    $firstValues = parse($firstFileContent, $firstFilePathParts['extension']);
    $secondValues = parse($secondFileContent, $secondFilePathParts['extension']);
    $diffTree = getDiffTree($firstValues, $secondValues);
    switch ($format) {
        case 'plain':
            $result = renderPlain($diffTree);
            break;
        case 'json':
            $result = renderJson($diffTree);
            break;
        default:
            $result = renderPretty($diffTree);
            break;
    }
    return $result;
}
function getDiffTree($firstObj, $secondObj)
{
    $firstData = get_object_vars($firstObj);
    $secondData = get_object_vars($secondObj);
    $merge = array_merge($firstData, $secondData);
    $mapped = array_map(function ($key, $value) use ($firstObj, $secondObj) {
        $node = [];
        if (!property_exists($firstObj, $key)) {
            $node['status'] = 'added';
            $node['key'] = $key;
            $node['oldValue'] = null;
            $node['newValue'] = $value;
            return $node;
        } elseif (!property_exists($secondObj, $key)) {
            $node['status'] = 'deleted';
            $node['key'] = $key;
            $node['oldValue'] = $value;
            $node['newValue'] = null;
            return $node;
        }

        $oldValue = $firstObj->$key;
        $newValue = $secondObj->$key;
        if (is_object($oldValue) && is_object($newValue)) {
            $node['status'] = 'parent';
            $node['key'] = $key;
            $node['oldValue'] = $oldValue;
            $node['newValue'] = $newValue;
            $node['children'] = getDiffTree($oldValue, $newValue);
        } else {
            $node['status'] = $oldValue === $newValue ? 'unchanged' : 'changed';
            $node['key'] = $key;
            $node['oldValue'] = $oldValue;
            $node['newValue'] = $newValue;
        }
        return $node;
    }, array_keys($merge), $merge);
    return collect($mapped);
}
