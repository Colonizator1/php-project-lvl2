<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\getDiff;

class DifferTest extends TestCase
{
    public function testDiff()
    {
        $fileOne = "tests/fixtures/before.json";
        $fileTwo = "tests/fixtures/after.json";
        $expected2 = file_get_contents("tests/fixtures/result");
        $this->assertEquals($expected2, getDiff($fileOne, $fileTwo));
    }
}
