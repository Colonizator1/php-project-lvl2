<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\getDiff;

class DifferTest extends TestCase
{
    public function testDiff()
    {
        $getFilePath = function ($fileName) {
            return __DIR__ . "/fixtures/{$fileName}";
        };

        $expected_pretty = file_get_contents($getFilePath("result_pretty"));
        $expected_plain = file_get_contents($getFilePath("result_plain"));
        $expected_json = file_get_contents($getFilePath("result_json"));

        $jsonOne = $getFilePath("before.json");
        $jsonTwo = $getFilePath("after.json");
        
        $yamlOne = $getFilePath("before.yaml");
        $yamlTwo = $getFilePath("after.yaml");

        $this->assertEquals($expected_pretty, getDiff($jsonOne, $jsonTwo));
        $this->assertEquals($expected_pretty, getDiff($yamlOne, $yamlTwo));

        $this->assertEquals($expected_plain, getDiff($jsonOne, $jsonTwo, "plain"));
        $this->assertEquals($expected_plain, getDiff($yamlOne, $yamlTwo, "plain"));

        $this->assertEquals($expected_json, getDiff($jsonOne, $jsonTwo, "json"));
        $this->assertEquals($expected_json, getDiff($yamlOne, $yamlTwo, "json"));
    }
}
