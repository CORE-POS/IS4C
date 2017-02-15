<?php

use COREPOS\pos\parser\Parser;

class BitCoinParser extends Parser
{
    public function check($str)
    {
        return $str == 'BITCOIN' ? true : false;
    }

    public function parse($str)
    {
        $info = new BitCoin();
        $ret = $this->default_json();
        $ret['main_frame'] = $info->pluginUrl() . '/gui/BitCoinAmountPage.php';

        return $ret;
    }
}

