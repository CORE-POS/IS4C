<?php

use COREPOS\pos\parser\Parser;

class StripeParser extends Parser
{
    public function check($str)
    {
        return $str == 'BITCOIN' || $str == 'STRIPE' ? true : false;
    }

    public function parse($str)
    {
        $info = new StripeDotCom();
        $ret = $this->default_json();
        if ($str == 'STRIPE') {
            $this->session->set('StripeMode', 'Credit');
        }
        $ret['main_frame'] = $info->pluginUrl() . '/gui/StripeAmountPage.php';

        return $ret;
    }
}

