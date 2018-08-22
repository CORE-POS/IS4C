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
        $getP = $dbc->prepare('SELECT upc, description, trans_type FROM localtemptrans WHERE trans_id=?');
        $cur = $dbc->getRow($getP, $this->session->get('currentid'));
        if (!is_array($cur) || $cur['trans_type'] != 'I') {
            $ret['output'] = DisplayLib::boxMsg(
                _('Not an item'),
                'Error',
                false,
                DisplayLib::standardClearButton()
            );

            return $ret;
        }

        $ret['output'] = $this->getInventory($cur['upc'], $cur['description']);

        return $ret;
    }

    private function getInventory($upc, $description)
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

        $invP = $dbc->prepare('SELECT onHand, countDate
            FROM ' . $this->session->get('InventoryOpDB') . $dbc->sep() . 'InventoryCache AS i
                INNER JOIN ' . $this->session->get('InventoryOpDB') . $dbc->sep() . 'InventoryCounts AS c
                    ON i.upc=c.upc AND i.storeID=c.storeID
            WHERE i.upc=?
                AND i.storeID=?
                AND c.mostRecent=1
            ORDER BY c.countDate DESC');
        $invData = $dbc->getRow($invP, array($upc, $this->session->get('store_id')));
        if ($invData === false) {
            return DisplayLib::boxMsg(
                _('No inventory data for this item'),
                'Error',
                false,
                DisplayLib::standardClearButton()
            );
        }

        $moreRecent = $dbc->prepare("SELECT SUM(quantity) AS qty
            FROM dlog
            WHERE upc=?
                AND store_id=?
                AND emp_no <> 9999
                AND register_no <> 99
                AND trans_status <> 'X'
                AND tdate > ?
        ");
        $moreRecent = $dbc->getValue($moreRecent, array($upc, $this->session->get('store_id'), $invData['countDate']));
        $moreRecent = $moreRecent ? $moreRecent : 0;

        //Get quantity sold in suspended transactions
        $suspended = 0;
        if ($this->session->get('InventoryIncludeSuspended')) {
            $invP = $dbc->prepare("SELECT SUM(quantity) AS qty
                FROM suspended
                WHERE upc=?
                    AND emp_no <> 9999
                    AND register_no <> 99
                    AND trans_status <> 'X'
                    AND datetime > ?");
            $suspended = $dbc->getValue($invP, array($upc, $invData['countDate']));
            $suspended = $suspended ? $suspended : 0;
        }

        return DisplayLib::boxMsg(
            _('Current Inventory: ') . ($invData['onHand'] - $moreRecent - $suspended),
            $upc . ':' . $description,
            false,
            DisplayLib::standardClearButton()
        );
    }
}

