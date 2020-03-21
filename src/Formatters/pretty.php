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
                $acc[] = getSpace($level) . "+ {$node['key']}: " . getStr($node['newValue'], $level);
            } elseif ($node['status'] == 'delete') {
                $acc[] = getSpace($level) . "- {$node['key']}: " . getStr($node['oldValue'], $level);
            } elseif ($node['status'] == 'unchanged') {
                $acc[] = getSpace($level) . "  {$node['key']}: " . getStr($node['oldValue'], $level);
            } else {
                $acc[] = getSpace($level) . "- {$node['key']}: " . getStr($node['oldValue'], $level);
                $acc[] = getSpace($level) . "+ {$node['key']}: " . getStr($node['newValue'], $level);
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

function getStrFromNode($collection, $level = 0)
{
    $nodeData = [];

    if (is_array($collection)) {
        $openBreacket = '[';
        $closeBreacket = ']';
        $nodeData[] = getSpace($level) . "$openBreacket";
        foreach ($collection as $value) {
            $nodeData[] = is_array($value) || is_object($value)
            ? getSpace($level) . "  " . getStrFromNode($value, $level + 1)
            : getSpace($level + 1) . "  " . getStr($value);
        }
        $nodeData[] = getSpace($level) . "  $closeBreacket";
    }
    if (is_object($collection)) {
        $openBreacket = '{';
        $closeBreacket = '}';
        $nodeData[] = "$openBreacket";
        foreach ($collection as $key => $value) {
            $nodeData[] = is_array($value) || is_object($value)
            ? getSpace($level) . "  $key: " . getStrFromNode($value, $level + 1)
            : getSpace($level + 1) . "  $key: " . getStr($value, $level);
        }
        $nodeData[] = getSpace($level) . "  $closeBreacket";
    }
    return implode("\n", $nodeData);
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
            return getStrFromNode($value, $level);
            break;
        case 'object':
            return getStrFromNode($value, $level);
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
