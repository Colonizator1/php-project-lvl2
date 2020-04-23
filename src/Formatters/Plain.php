<?php

namespace Differ\Formatters\Plain;

use Tightenco\Collect;

use function Differ\Functions\simpleValueToString;
use function Differ\Functions\isSimpleValue;

function renderPlain($tree)
{
    $myMap = function ($tree, $key = '') use (&$myMap) {
        return $tree->map(function ($node) use (&$myMap, $key) {
            $newValue = isSimpleValue($node['newValue']) ?
            simpleValueToString($node['newValue']) : 'complex value';

            $oldValue = isSimpleValue($node['oldValue']) ?
            simpleValueToString($node['oldValue']) : 'complex value';

            $children = $node['children'] ?? null;

            $keys = $key ? [$key, $node['key']] : [$node['key']];
            
            $fullKey = implode('.', $keys);

            switch ($node['status']) {
                case 'added':
                    $result = "Property '{$fullKey}' was added  with value: '{$newValue}'\n";
                    break;
                case 'deleted':
                    $result = "Property '{$fullKey}' was removed\n";
                    break;
                case 'unchanged':
                    $result = null;
                    break;
                case 'changed':
                    $result = "Property '{$fullKey}' was changed. From '{$oldValue}' to '{$newValue}'\n";
                    break;
                case 'parent':
                    $result = $myMap($node['children'], $fullKey);
                    break;
            }
            return $result;
        })->filter(function ($value) {
            return $value;
        })->implode("");
    };
    return $myMap($tree);
}
