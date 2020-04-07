<?php

namespace Differ\parser;

use Symfony\Component\Yaml\Yaml;

function parse($filepath)
{
    try {
        if (!file_exists($filepath)) {
            throw new InvalidArgumentException("File by path: {$filepath} doesn't exist");
        }
        $content = file_get_contents($filepath);
        if ($content === false) {
            throw new \Exception("Can't read file by: {$filepath}");
        }
        $path_parts = pathinfo($filepath);
        
        if ($path_parts['extension'] == 'json') {
            return json_decode($content);
        } elseif ($path_parts['extension'] == 'yaml') {
            return Yaml::parse($content, Yaml::PARSE_OBJECT_FOR_MAP);
        }
            throw new \Exception("Wrong file extention. Got: {$path_parts['extension']}. Need json or yaml");
    } catch (\Exception $e) {
        echo $e->getMessage(), "\n";
        exit();
    }
}
