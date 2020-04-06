<?php

namespace Differ\Formatters\pretty;

use Tightenco\Collect;

function renderPretty($tree)
{
    $render = function ($tree, $level = 0) use (&$render) {
        $result = $tree->reduce(function ($acc, $node) use ($level, &$render) {
            if (!$node['children']) {
                if ($node['status'] == 'added') {
                    $acc[] = getSpace($level) . "+ " . getString($node['newValue'], $node['key'], $level);
                } elseif ($node['status'] == 'deleted') {
                    $acc[] = getSpace($level) . "- " . getString($node['oldValue'], $node['key'], $level);
                } elseif ($node['status'] == 'changed') {
                    $acc[] = getSpace($level) . "- " . getString($node['oldValue'], $node['key'], $level);
                    $acc[] = getSpace($level) . "+ " . getString($node['newValue'], $node['key'], $level);
                } else {
                    $acc[] = getSpace($level) . "  " . getString($node['oldValue'], $node['key'], $level);
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

function getString($value, $keyValue = '', $level = 0)
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
        return getSpace($level + 1) . "  " . getString($item, $key, $level + 1);
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
