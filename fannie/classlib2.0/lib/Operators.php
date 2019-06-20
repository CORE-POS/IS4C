<?php

namespace COREPOS\Fannie\API\lib;

class Operators
{
    public static function div($a, $b)
    {
        return $b != 0 ? $a / $b : 0;
    }
}

