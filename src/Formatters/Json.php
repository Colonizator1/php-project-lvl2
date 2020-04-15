<?php

namespace Differ\Formatters\Json;

use Tightenco\Collect;

function renderJson($tree)
{
    return $tree->toJson();
}
