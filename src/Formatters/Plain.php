<?php

namespace Differ\Formatters\Plain;

use Tightenco\Collect;

use function Differ\Functions\simpleValueToString;
use function Differ\Functions\isSimpleValue;

function renderPlain($tree)
{
    $myMap = function ($tree, $key = '') use (&$myMap) {
        return $tree->map(function ($node) use (&$myMap, $key) {
            $newValue = getPlainValue($node['newValue']);
            $oldValue = getPlainValue($node['oldValue']);

            $keys = $key ? [$key, $node['key']] : [$node['key']];
            $fullKey = implode('.', $keys);

            switch ($node['status']) {
                case 'added':
                    $result = "Property '{$fullKey}' was added  with value: '{$newValue}'";
                    break;
                case 'deleted':
                    $result = "Property '{$fullKey}' was removed";
                    break;
                case 'unchanged':
                    $result = null;
                    break;
                case 'changed':
                    $result = "Property '{$fullKey}' was changed. From '{$oldValue}' to '{$newValue}'";
                    break;
                case 'parent':
                    $result = $myMap($node['children'], $fullKey);
                    break;
            }
            return $result;
        })->filter(function ($value) {
            return $value;
        })->implode("\n");
    };
    return $myMap($tree);
}

function getPlainValue($value)
{
    return isSimpleValue($value) ? simpleValueToString($value) : 'complex value';
}
