<?php

use COREPOS\pos\parser\PreParser;

class IntEndPreParser extends PreParser
{
    /**
     * Check for command that will end the transaction
     * 1. amount followed by two letter code. Verify the amount will
     *    be sufficient to end the transaction
     * 2. Any Paycards launch command
     * 3. A two letter entry that is a tender code
     */
    public function check($str)
    {
        if ($this->session->get('Intercepted')) {
            return false;
        }

        if (is_numeric(substr($str, 0, strlen($str)-2)) && !is_numeric(substr($str, -2))) {
            $amt = substr($str, 0, strlen($str)-2);
            $amt /= 100;
            if (abs($this->session->get('amtdue') - $amt) < 0.005) {
                return true;
            }
        } elseif (substr($str, 0, 7) == 'DATACAP') {
            return true;
        } elseif (strlen($str) == 2 && !is_numeric($str)) {
            $map = $this->session->get("TenderMap");
            if (is_array($map) && isset($map[$str])) {
                return true;
            }
        }

        return false;
    }

    public function parse($str)
    {
        if (!$this->session->get('Intercepted')) {
            $str = 'INTERCEPT' . $str;
            $this->session->set('Intercepted', 1);
        }

        return $str;
    }
}

