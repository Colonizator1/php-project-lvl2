<?php

namespace Differ\Formatters\plain;

use Tightenco\Collect;

use function Differ\Formatters\pretty\toString;

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
        $newValue = is_array($node['newValue']) || is_object($node['newValue']) ?
        'complex value' : toString($node['newValue']);

        $oldValue = is_array($node['oldValue']) || is_object($node['oldValue']) ?
        'complex value' : toString($node['oldValue']);

        if ($node['status'] == 'added') {
            return "Property '{$key}' was added  with value: '{$newValue}'\n";
        } elseif ($node['status'] == 'deleted') {
            return "Property '{$key}' was removed\n";
        } elseif ($node['status'] == 'changed') {
            return "Property '{$key}' was changed. From '{$oldValue}' to '{$newValue}'\n";
        }
        return null;
    });
    $filtered = $mapped->filter(function ($value) {
        return $value;
    });
    return $filtered->implode("");
}
