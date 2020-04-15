<?php

namespace Differ\Parser;

use Symfony\Component\Yaml\Yaml;

function parse($content, $type)
{
    if ($type == 'json') {
        $result = json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("json error: " . json_last_error());
        }
        return $result;
    } elseif ($type == 'yaml') {
        return Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
    } else {
        throw new \Exception("Wrong file extention. Got: {$type}. Need json or yaml");
    }
}
