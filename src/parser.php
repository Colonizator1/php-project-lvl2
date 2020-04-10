<?php

namespace Differ\parser;

use Symfony\Component\Yaml\Yaml;

function parse($content, $type)
{
    if ($type == 'json') {
        return json_decode($content);
    } elseif ($type == 'yaml') {
        return Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
    } else {
        throw new \Exception("Wrong file extention. Got: {$type}. Need json or yaml");
    }
}
