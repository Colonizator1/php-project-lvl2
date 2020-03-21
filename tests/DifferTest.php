<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\diff\getDiff;

class DifferTest extends TestCase
{
    public function testDiff()
    {
        $expected = file_get_contents("tests/fixtures/result");

        $jsonOne = "tests/fixtures/before.json";
        $jsonTwo = "tests/fixtures/after.json";
        $this->assertEquals($expected, getDiff($jsonOne, $jsonTwo));

        $yamlOne = "tests/fixtures/before.yaml";
        $yamlTwo = "tests/fixtures/after.yaml";
        $this->assertEquals($expected, getDiff($yamlOne, $yamlTwo));
    }
}
