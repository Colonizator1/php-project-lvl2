<?php

namespace Differ\Formatters\json;

use Tightenco\Collect;

function renderJson($tree)
{
    return $tree->toJson();
}
