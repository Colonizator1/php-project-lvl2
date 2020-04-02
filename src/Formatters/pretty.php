<?php

namespace Differ\Formatters\pretty;

use Tightenco\Collect;
use Symfony\Component\Yaml\Yaml;
use Funct\Collection;

function renderPretty($tree)
{
    $render = function ($tree, $level = 0) use (&$render) {
        $result = $tree->reduce(function ($acc, $node) use ($level, &$render) {
            if (!$node['children']) {
                if ($node['status'] == 'added') {
                    $acc[] = getSpace($level) . "+ " . getString($node['key'], $node['newValue'], $level);
                } elseif ($node['status'] == 'deleted') {
                    $acc[] = getSpace($level) . "- " . getString($node['key'], $node['oldValue'], $level);
                } elseif ($node['status'] == 'changed') {
                    $acc[] = getSpace($level) . "- " . getString($node['key'], $node['oldValue'], $level);
                    $acc[] = getSpace($level) . "+ " . getString($node['key'], $node['newValue'], $level);
                } else {
                    $acc[] = getSpace($level) . "  " . getString($node['key'], $node['oldValue'], $level);
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

function getString($keyNode, $data, $level)
{
    if (isSimpleValue($data)) {
        $keyStr = $keyNode ? "$keyNode: " : '';
        return "$keyStr" . toString($data);
    }
    $result = [];
    if (is_array($data)) {
        $coolection = collect($data);
        $keyStr = $keyNode ? "$keyNode: [" : getSpace($level) . '  [';
        $result[] = "{$keyStr}";
        $result[] = $coolection->map(function ($item) use (&$level) {
            return isSimpleValue($item) ? getSpace($level + 1) . "  " . toString($item) : getString('', $item, $level + 1);
        })->implode("\n");
        $result[] = getSpace($level) . "  ]";
    }

    if (is_object($data)) {
        $coolection = collect(get_object_vars($data));
        $keyStr = $keyNode ? "$keyNode: {" : getSpace($level) . '  {';
        $result[] = "{$keyStr}";
        $result[] = $coolection->map(function ($item, $key) use (&$level) {
            $space = getSpace($level + 1) . "  ";
            return isSimpleValue($item) ? "{$space}{$key}: " . toString($item) : $space . getString($key, $item, $level + 1);
        })->implode("\n");
        $result[] = getSpace($level) . "  }";
    }
    return implode("\n", $result);
}

function toString($value, $level = 0)
{
    switch (gettype($value)) {
        case 'boolean':
            return $value ? 'true' : 'false';
            break;
        case 'NULL':
            return "null";
            break;
        case 'array':
            return 'array';
            break;
        case 'object':
            return 'object';
            break;
        default:
            return "$value";
            break;
    }
}
function isSimpleValue($value)
{
    switch (gettype($value)) {
        case 'boolean':
            return true;
            break;
        case 'NULL':
            return true;
            break;
        case 'array':
            return false;
            break;
        case 'object':
            return false;
            break;
        default:
            return true;
            break;
    }
}

function getSpace(int $level)
{
    $spacesCol = 2 + 4 * $level;
    return str_repeat(" ", $spacesCol);
}
