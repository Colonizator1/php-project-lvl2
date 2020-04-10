<?php

namespace Differ;

use Tightenco\Collect;

use function Differ\parser\parse;
use function Differ\Formatters\pretty\renderPretty;
use function Differ\Formatters\plain\renderPlain;
use function Differ\Formatters\json\renderJson;

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
    --format <fmt>                Report format: pretty, plain, json [default: pretty]
DOC;
    $result = \Docopt::handle($doc, array('version' => '1.0', 'firstFile', 'secondFile'));
    $firstFile = $result->args['<firstFile>'];
    $secondFile = $result->args['<secondFile>'];
    $format = $result->args['--format'];
    print_r(getDiff($firstFile, $secondFile, $format));
}

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
    try {
        $firstValues = parse($firstFileContent, $firstFilePathParts['extension']);
        $secondValues = parse($secondFileContent, $secondFilePathParts['extension']);
    } catch (\Exception $e) {
        echo $e->getMessage(), "\n";
    }
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
    $mapped = function ($firstTree, $secondTree) use (&$mapped) {
        $firstMapped = $firstTree->map(function ($firstValue, $firstKey) use ($secondTree, &$mapped) {
            if ($secondTree->has($firstKey)) {
                $secondValue = $secondTree->get($firstKey);
                $status = $firstValue == $secondValue ? "unchanged" : "changed";
            } else {
                $status = "deleted";
                $secondValue = null;
            }
            $oldValue = $firstValue;
            $newValue = $secondValue;
            return is_object($firstValue) && is_object($secondValue) ? collect([
                'status' => $status,
                'key' => $firstKey,
                'oldValue' => $oldValue,
                'newValue' => $newValue,
                'children' => $mapped(collect($firstValue), collect($secondValue)),
            ]) :
            collect([
                'status' => $status,
                'key' => $firstKey,
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
    $collectionFirst = collect($firstData);
    $collectionSecond = collect($secondData);
    return collect($mapped($collectionFirst, $collectionSecond));
}
