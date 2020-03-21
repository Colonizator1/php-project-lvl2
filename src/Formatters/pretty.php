<?php

namespace Differ\Formatters\pretty;

use Tightenco\Collect;
use Symfony\Component\Yaml\Yaml;
use Funct\Collection;

function renderPretty($tree, $level = 0)
{
    /*
    $map = function ($tree, $closure, $level = 1) use (&$map, $space) {
        $result = array_map(function ($node) use (&$map, $closure, $space, $level) {
            $children = $node['children'] ?? null;
            if (!$children) {
                return $closure($node);
            } else {
                return ["$space  {$node['key']}: {", $map($children, $closure, ++$level), "$space  }"];
                //return array_merge($node, ['children' => $map($children, $closure)]);
            }
        }, $tree);
        //return $result;
        return Collection\flattenAll($result);
    };
    $result = $map($tree, function ($node) use ($space) {
        if ($node['status'] == 'add') {
            $acc = "{$space}+ {$node['key']}: " . getStr($node['newValue'], 1);
        } elseif ($node['status'] == 'delete') {
            $acc = "{$space}- {$node['key']}: " . getStr($node['oldValue'], 1);
        } elseif ($node['status'] == 'unchanged') {
            $acc = "{$space}  {$node['key']}: " . getStr($node['oldValue'], 1);
        } else {
            $acc = "{$space}- {$node['key']}: " . getStr($node['oldValue'], 1) .
            "\n{$space}+ {$node['key']}: " . getStr($node['newValue'], 1);
        }
        return $acc;
    });
    print_r(implode("\n", $result));*/

    $result = array_reduce($tree, function ($acc, $node) use ($level) {
        if (!$node['children']) {
            if ($node['status'] == 'add') {
                $acc[] = getSpace($level) . "+ " . getString($node['key'], $node['newValue'], $level);
            } elseif ($node['status'] == 'delete') {
                $acc[] = getSpace($level) . "- " . getString($node['key'], $node['oldValue'], $level);
            } elseif ($node['status'] == 'unchanged') {
                $acc[] = getSpace($level) . "  " . getString($node['key'], $node['oldValue'], $level);
            } else {
                $acc[] = getSpace($level) . "- " . getString($node['key'], $node['oldValue'], $level);
                $acc[] = getSpace($level) . "+ " . getString($node['key'], $node['newValue'], $level);
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
            $acc[] = getSpace($level) . "$sign {$node['key']}: {";
            $acc[] = renderPretty($node['children'], $level + 1);
            $acc[] = getSpace($level) . "  }";
        }
        return $acc;
    }, []);
    return implode("\n", $result);
}
function getString($keyNode, $data, $level)
{
    $keyStr = $keyNode ? "$keyNode: " : '';
    $result = [];
    if (is_array($data)) {
        $result[] = getSpace($level) . "{$keyStr}[";
        foreach ($data as $value) {
            $result[] = is_array($value) || is_object($value)
            ? getSpace($level) . getString('', $value, $level + 1)
            : getSpace($level + 1) . "  " . getString('', $value, $level + 1);
        }
        $result[] = getSpace($level) . "  ]";
    } elseif (is_object($data)) {
        $result[] = getSpace($level) . "{$keyStr}{";
        foreach ($data as $key => $value) {
            $result[] = is_array($value) || is_object($value)
            ? getSpace($level) . getString($key, $value, $level + 1)
            : getSpace($level + 1) . "  " . getString($key, $value, $level + 1);
        }
        $result[] = getSpace($level) . "  }";
    } else {
        return "$keyStr" . getStr($data);
    }
    return implode("\n", $result);
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
            return getString('', $value, $level);
            break;
        case 'object':
            return getString('', $value, $level);
            break;
        default:
            return "$value";
            break;
    }
}

function getSpace(int $level)
{
    $spacesCol = 2 + 4 * $level;
    return str_repeat(" ", $spacesCol);
}
