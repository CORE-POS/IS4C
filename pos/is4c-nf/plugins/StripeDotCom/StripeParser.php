<?php

use COREPOS\pos\parser\Parser;

class StripeParser extends Parser
{
    public function check($str)
    {
        return $str == 'BITCOIN' ? true : false;
    }

    public function parse($str)
    {
        $info = new StripeDotCom();
        $ret = $this->default_json();
        $ret['main_frame'] = $info->pluginUrl() . '/gui/StripeAmountPage.php';

        return $ret;
    }
}

