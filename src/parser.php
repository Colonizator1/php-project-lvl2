<?php

namespace Differ\parser;

use Symfony\Component\Yaml\Yaml;

function parse($path)
{
    try {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("File by path: {$path} doesn't exist");
        }
        $file = file_get_contents($path);
        if ($file === false) {
            throw new \Exception("Can't read file by: {$path}");
        }
        $path_parts = pathinfo($path);
        
        if ($path_parts['extension'] == 'json') {
            return json_decode($file);
        } elseif ($path_parts['extension'] == 'yaml') {
            return Yaml::parse($file, Yaml::PARSE_OBJECT_FOR_MAP);
        }
            throw new \Exception("Wrong file extention. Got: {$path_parts['extension']}. Need json or yaml");
    } catch (\Exception $e) {
        echo $e->getMessage(), "\n";
        exit();
    }
}
