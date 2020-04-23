<?php

namespace Differ\Functions;

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
