<?php

use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;

class InventoryCheck extends Parser
{
    public function check($str)
    {
        return $str === 'INV';
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        $dbc = Database::tDataConnect();
        $getP = $dbc->prepare('SELECT upc, trans_type FROM localtemptrans WHERE trans_id=?');
        $cur = $dbc->getRow($getP, $this->session->get('LastID'));
        if (!is_array($cur) || $cur['trans_type'] != 'I') {
            $ret['output'] = DisplayLib::boxMsg(
                _('Not an item'),
                'Error',
                false,
                DisplayLib::standardClearButton()
            );

            return $ret;
        }

        $ret['output'] = $this->getInventory($cur['upc']);

        return $ret;
    }

    private function getInventory($upc)
    {
        $dbc = Database::mDataConnect();
        if ($dbc === false || !$dbc->isConnected()) {
            return DisplayLib::boxMsg(
                _('Inventory unavailable'),
                'Error',
                false,
                DisplayLib::standardClearButton()
            );
        }

        $invP = $dbc->prepare('SELECT onHand
            FROM ' . $this->session->get('InventoryOpDB') . $dbc->sep() . 'InventoryCache
            WHERE upc=?
                AND storeID=?');
        $inv1 = $dbc->getValue($invP, array($upc, $this->session->get('store_id')));
        if ($inv1 === false) {
            return DisplayLib::boxMsg(
                _('No inventory data for this item'),
                'Error',
                false,
                DisplayLib::standardClearButton()
            );
        }

        $inv2 = $dbc->prepare("SELECT SUM(quantity) AS qty
            FROM dlog
            WHERE upc=?
                AND store_id=?
                AND emp_no <> 9999
                AND register_no <> 99
                AND trans_status <> 'X'
        ");
        $inv2 = $dbc->getValue($inv2, array($upc, $this->session->store_id));
        $inv2 = $inv2 ? $inv2 : 0;

        return DisplayLib::boxMsg(
            _('Current Inventory: ') . ($inv1 - $inv2),
            $upc,
            false,
            DisplayLib::standardClearButton()
        );
    }
}

