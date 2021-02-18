<?php

use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;


class ItemLimitParser extends Parser
{
    public function check($str)
    {
        if (is_numeric($str) && strlen($str) < 16) {

            $upc = str_pad($str, 13, '0', STR_PAD_LEFT);
            $dbc = Database::pDataConnect();
            $limitP = $dbc->prepare("SELECT limit FROM ItemLimits WHERE upc=?");
            $limit = $dbc->getValue($limitP, array($upc));
            if (!$limit) {
                return false;
            }
            
            $dbc = Database::tDataConnect();
            $qtyP = $dbc->prepare("SELECT SUM(quantity) FROM localtemptrans WHERE upc=?");
            $qty = $dbc->getValue($qtyP, array($upc));
            if ($qty && $qty >= $limit) {
                return true;
            }
        }

        return false;
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        $ret['output'] = DisplayLib::boxMsg(
            _('Maximum quantity already purchased'),
            _('Limit Reached'),
            false,
            DisplayLib::standardClearButton()
        );

        return $ret;
    }
}

