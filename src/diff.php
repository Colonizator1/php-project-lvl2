<?php

namespace Differ\diff;

use Tightenco\Collect;
use Symfony\Component\Yaml\Yaml;

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
    $objTree = getDiffTree($firstValues, $secondValues);

    switch ($format) {
        case 'plain':
            $result = renderPlain($objTree);
            break;
        case 'json':
            $result = renderJson($objTree);
            break;
        default:
            $result = renderPretty($objTree);
            break;
    }
    return $result;
}

function parse($path)
{
    try {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("File by path: {$path} doesn't exist");
        }
        $file = file_get_contents($path);
        if ($file === false) {
            throw new \Exception("Can't read file by: {$path}");
        }
        $path_parts = pathinfo($path);
        
        if ($path_parts['extension'] == 'json') {
            return json_decode($file);
        } elseif ($path_parts['extension'] == 'yaml') {
            return Yaml::parse($file, Yaml::PARSE_OBJECT_FOR_MAP);
        }
            throw new \Exception("Wrong file extention. Got: {$path_parts['extension']}. Need json or yaml");
    } catch (\Exception $e) {
        echo $e->getMessage(), "\n";
        exit();
    }
}

function getDiffTree($firstNode, $secondNode)
{
    $collectionFirst = collect($firstNode);
    $collectionSecond = collect($secondNode);
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
    return collect($mapped($collectionFirst, $collectionSecond));
}
