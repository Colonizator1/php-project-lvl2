<?php

namespace Differ\Parser;

use Symfony\Component\Yaml\Yaml;

function parse($content, $type)
{
    switch ($type) {
        case 'json':
            $result = json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("json error: " . json_last_error());
            }
            return $result;
        case 'yaml':
            return Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
        default:
            throw new \Exception("Wrong file extention. Got: {$type}. Need json or yaml");
    }
}
