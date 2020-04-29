<?php

namespace Differ\Formatters\Pretty;

use Tightenco\Collect;

use function Differ\Functions\simpleValueToString;
use function Differ\Functions\isSimpleValue;

function renderPretty($tree, $level = 0)
{
    $result = $tree->reduce(function ($acc, $node) use ($level) {
        $newValue = getPrettyNode($node['newValue'], $node['key'], $level);
        $oldValue = getPrettyNode($node['oldValue'], $node['key'], $level);
        switch ($node['status']) {
            case 'added':
                $acc[] = getSpace($level) . "+ {$newValue}";
                break;
            case 'deleted':
                $acc[] = getSpace($level) . "- {$oldValue}";
                break;
            case 'unchanged':
                $acc[] = getSpace($level) . "  {$oldValue}";
                break;
            case 'changed':
                $acc[] = getSpace($level) . "- {$oldValue}";
                $acc[] = getSpace($level) . "+ {$newValue}";
                break;
            case 'parent':
                $acc[] = getSpace($level) . "  {$node['key']}: {";
                $acc[] = renderPretty($node['children'], $level + 1);
                $acc[] = getSpace($level) . "  }";
                break;
        }
        return $acc;
    }, []);
    if ($level == 0) {
        return "{\n" . implode("\n", $result) . "\n}";
    }
    return implode("\n", $result);
}

function getPrettyNode($value, $keyValue = '', $level = 0)
{
    if (isSimpleValue($value)) {
        return $keyValue ?
        "$keyValue: " . simpleValueToString($value) :
        simpleValueToString($value);
    }

    if (is_array($value)) {
        $collection = collect($value);
        $openBracket = "[";
        $closeBracket = "]";
    } elseif (is_object($value)) {
        $collection = collect(get_object_vars($value));
        $openBracket = "{";
        $closeBracket = "}";
    }

    $result = [];
    $result[] = $keyValue ? "$keyValue: $openBracket" : "$openBracket";
    $result[] = $collection->map(function ($item, $key) use (&$level, $value) {
        $key = is_array($value) ? '' : $key;
        return getSpace($level + 1) . "  " . getPrettyNode($item, $key, $level + 1);
    })->implode("\n");
    $result[] = getSpace($level) . "  $closeBracket";

    return implode("\n", $result);
}

function getSpace(int $level)
{
    $spacesCol = 2 + 4 * $level;
    return str_repeat(" ", $spacesCol);
}
