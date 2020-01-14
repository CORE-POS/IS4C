<?php

use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\parser\PreParser;

class AccessProgramPreParser extends PreParser
{
    public function check($str)
    {
        return $str === 'VD';
    }

    public function parse($str)
    {
        $item = PrehLib::peekItem(true, $this->session->get('currentid'));
        if ($item['upc'] == '0000000010730') {
            return 'VD10730';
        }

        return $str;
    }
}

