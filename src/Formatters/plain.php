<?php

namespace Differ\Formatters\plain;

use Tightenco\Collect;
use Symfony\Component\Yaml\Yaml;
use Funct\Collection;

use function Differ\Formatters\pretty\getStr;

function renderPlain($tree)
{
    $map = function ($tree, $closure, $key = '') use (&$map) {
        return Collection\flattenAll(array_map(function ($node) use (&$closure, &$map, $key) {
            $children = $node['children'] ?? null;
            $key = $key ? "$key.{$node['key']}" : $node['key'];
            if (!$children) {
                return $closure($key, $node);
            } else {
                return $map($node['children'], $closure, $key);
            }
        }, $tree));
    };
    $result = $map($tree, function ($key, $node) {
        $newValue = is_array($node['newValue']) || is_object($node['newValue']) ?
        'complex value' : getStr($node['newValue']);

        $oldValue = is_array($node['oldValue']) || is_object($node['oldValue']) ?
        'complex value' : getStr($node['oldValue']);

        if ($node['status'] == 'add') {
            return "Property '{$key}' was added  with value: '{$newValue}'\n";
        } elseif ($node['status'] == 'delete') {
            return "Property '{$key}' was removed\n";
        } elseif ($node['status'] == 'changed') {
            return "Property '{$key}' was changed. From '{$oldValue}' to '{$newValue}'\n";
        }
        return null;
    });

    $filtered = array_filter($result, function ($value) {
        return $value;
    });
    
    return implode("", $filtered);
}
