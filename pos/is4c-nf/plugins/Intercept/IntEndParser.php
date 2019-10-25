<?php

use COREPOS\pos\parser\Parser;

class IntEndParser extends Parser
{
    public function check($str)
    {
        return substr($str, 0, 9) == 'INTERCEPT';
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        $self = new Intercept();
        $ret['main_frame'] = $self->pluginUrl()
            . '/InterceptPage.php';
        $this->session->set('InterceptedCommand', substr($str, 9));

        return $ret;
    }
}

