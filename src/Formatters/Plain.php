<?php

namespace Differ\Formatters\Plain;

use Tightenco\Collect;

use function Differ\Formatters\pretty\simpleValueToString;
use function Differ\Formatters\pretty\isSimpleValue;

function renderPlain($tree)
{
    $map = function ($tree, $closure, $key = '') use (&$map) {
        return $tree->map(function ($node) use (&$closure, &$map, $key) {
            $children = $node['children'] ?? null;
            $key = $key ? "$key.{$node['key']}" : $node['key'];
            if (!$children) {
                return $closure($key, $node);
            } else {
                return $map($node['children'], $closure, $key);
            }
        })->flatten();
    };
    $mapped = $map($tree, function ($key, $node) {
        $newValue = isSimpleValue($node['newValue']) ?
        simpleValueToString($node['newValue']) : 'complex value';

        $oldValue = isSimpleValue($node['oldValue']) ?
        simpleValueToString($node['oldValue']) : 'complex value';

        if ($node['status'] == 'added') {
            $result = "Property '{$key}' was added  with value: '{$newValue}'\n";
        } elseif ($node['status'] == 'deleted') {
            $result = "Property '{$key}' was removed\n";
        } elseif ($node['status'] == 'changed') {
            $result = "Property '{$key}' was changed. From '{$oldValue}' to '{$newValue}'\n";
        } elseif ($node['status'] == 'unchanged') {
            $result = null;
        }
        return $result;
    })->filter(function ($value) {
        return $value;
    })->implode("");

    return $mapped;
}
