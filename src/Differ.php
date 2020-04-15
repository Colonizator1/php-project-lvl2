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
    if (!$firstFileContent) {
        throw new \Exception("Can't read file by: {$firstFilePath}");
    }
    if (!$secondFileContent) {
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

function getDiffTree($firstData, $secondData)
{
    $buildTree = function ($firstObj, $secondObj) use (&$buildTree) {
        $unionObj = $firstObj->merge($secondObj);
        return $unionObj->map(function ($value, $key) use ($firstObj, $secondObj, &$buildTree) {
            if ($firstObj->has($key) === false) {
                $status = 'added';
                $oldValue = null;
                $newValue = $value;
                $children = null;
            } elseif ($secondObj->has($key) === false) {
                $status = 'deleted';
                $oldValue = $value;
                $newValue = null;
                $children = null;
            } else {
                $oldValue = $firstObj->get($key);
                $newValue = $secondObj->get($key);
                $status = $oldValue == $newValue ? 'unchanged' : "changed";
                $children = is_object($oldValue) && is_object($newValue) ?
                $buildTree(collect($oldValue), collect($newValue)) : null;
            }
            return collect([
                'status' => $status,
                'key' => $key,
                'oldValue' => $oldValue,
                'newValue' => $newValue,
                'children' => $children
            ]);
        });
    };
    $collectionFirst = collect($firstData);
    $collectionSecond = collect($secondData);
    return $buildTree($collectionFirst, $collectionSecond);
}
