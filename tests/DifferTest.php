<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\getDiff;

class DifferTest extends TestCase
{
    public function testDiff()
    {
        $expected_pretty = file_get_contents("tests/fixtures/result_pretty");
        $expected_plain = file_get_contents("tests/fixtures/result_plain");
        $expected_json = file_get_contents("tests/fixtures/result_json");

        $jsonOne = "tests/fixtures/before.json";
        $jsonTwo = "tests/fixtures/after.json";
        
        $yamlOne = "tests/fixtures/before.yaml";
        $yamlTwo = "tests/fixtures/after.yaml";

        $this->assertEquals($expected_pretty, getDiff($jsonOne, $jsonTwo));
        $this->assertEquals($expected_pretty, getDiff($yamlOne, $yamlTwo));

        $this->assertEquals($expected_plain, getDiff($jsonOne, $jsonTwo, "plain"));
        $this->assertEquals($expected_plain, getDiff($yamlOne, $yamlTwo, "plain"));

        $this->assertEquals($expected_json, getDiff($jsonOne, $jsonTwo, "json"));
        $this->assertEquals($expected_json, getDiff($yamlOne, $yamlTwo, "json"));
    }
}
