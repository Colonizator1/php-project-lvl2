<?php

namespace Differ\Formatters\pretty;

use Tightenco\Collect;

function renderPretty($tree)
{
    $render = function ($tree, $level = 0) use (&$render) {
        $result = $tree->reduce(function ($acc, $node) use ($level, &$render) {
            if (!$node['children']) {
                $newValue = getPretty($node['newValue'], $node['key'], $level);
                $oldValue = getPretty($node['oldValue'], $node['key'], $level);
                if ($node['status'] == 'added') {
                    $acc[] = getSpace($level) . "+ {$newValue}";
                } elseif ($node['status'] == 'deleted') {
                    $acc[] = getSpace($level) . "- {$oldValue}";
                } elseif ($node['status'] == 'changed') {
                    $acc[] = getSpace($level) . "- {$oldValue}";
                    $acc[] = getSpace($level) . "+ {$newValue}";
                } else {
                    $acc[] = getSpace($level) . "  {$oldValue}";
                }
            } else {
                $acc[] = getSpace($level) . "  {$node['key']}: {";
                $acc[] = implode("\n", $render($node['children'], $level + 1));
                $acc[] = getSpace($level) . "  }";
            }
            return $acc;
        }, []);
        return $result;
    };
    return "{\n" . implode("\n", $render($tree)) . "\n}\n";
}

function getPretty($value, $keyValue = '', $level = 0)
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
        return getSpace($level + 1) . "  " . getPretty($item, $key, $level + 1);
    })->implode("\n");
    $result[] = getSpace($level) . "  $closeBracket";

    return implode("\n", $result);
}

function simpleValueToString($value)
{
    switch (gettype($value)) {
        case 'boolean':
            return $value ? 'true' : 'false';
            break;
        case 'NULL':
            return "null";
            break;
        default:
            return "$value";
            break;
    }
}
function isSimpleValue($value)
{
    $type = gettype($value);
    return $type == 'array' || $type == 'object' ? false : true;
}

function getSpace(int $level)
{
    $spacesCol = 2 + 4 * $level;
    return str_repeat(" ", $spacesCol);
}
