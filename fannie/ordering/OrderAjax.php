<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!class_exists('OrderNotifications')) {
    include(__DIR__ . '/OrderNotifications.php');
}
if (!class_exists('SoPoBridge')) {
    include(__DIR__ . '/SoPoBridge.php');
}

class OrderAjax extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute(
            'get<id><comms>',
            'post<id><commID>',
            'post<id><status>', 
            'post<id><ctc>',
            'post<id><pn>',
            'post<id><confirm>',
            'post<id><store>',
            'post<id><close>',
            'post<id><testNotify>',
            'post<id><nodupe>',
            'post<upc><discounttype>',
        );

        return parent::preprocess();
    }

    private function tdb()
    {
        $this->connection->selectDB($this->config->get('TRANS_DB'));
        return $this->connection;
    }

    private function odb()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        return $this->connection;
    }

    protected function get_id_comms_handler()
    {
        $dbc =  $this->tdb();
        $prep = $dbc->prepare("SELECT tdate, channel, message FROM SpecialOrderCommLog WHERE specialOrderID=? ORDER BY tdate DESC");
        $res = $dbc->execute($prep, array($this->id));
        $ret = '<table class="table table-bordered table-striped">';
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                $row['tdate'], $row['channel'], $row['message']);
        }
        $ret .= '</table>';
        echo $ret;

        return false;
    }

    protected function post_id_commID_handler()
    {
        $dbc =  $this->tdb();
        $msg = '';
        switch ($this->commID) {
        case 1:
            $msg = 'Your order did not arrive today, we will re-order it.';
            break;
        case 2:
            $msg = 'Your special order is not available and we cannot currently order this item for you due to outages from the supplier.';
            $this->close = 8;
            $this->post_id_close_handler();
            break;
        }
        if (!is_numeric($this->commID) && strlen($this->commID) > 25) {
            $msg = $this->commID;
        }

        if ($msg) {
            $email = new OrderNotifications($dbc);
            $email->sendGenericMessage($this->id, $msg);
        }

        echo $this->get_id_comms_handler();

        return false;
    }

    protected function post_id_close_handler()
    {
        // update status
        $this->status = $this->close;
        $this->post_id_status_handler();

        $dbc = $this->tdb();
        $moveP = $dbc->prepare("INSERT INTO CompleteSpecialOrder
                SELECT * FROM PendingSpecialOrder
                WHERE order_id=?");
        $dbc->execute($moveP, array($this->id));

        $itemP = $dbc->prepare("SELECT s.storeID, p.order_id, p.trans_id 
                FROM " . FannieDB::fqn('PendingSpecialOrder', 'trans') . " AS p
                    LEFT JOIN " . FannieDB::fqn('SpecialOrders', 'trans') . " AS s ON p.order_id=s.specialOrderID
                WHERE p.order_id=?
                    AND p.trans_id > 0 AND p.deleted=0");
        $bridge = new SoPoBridge($dbc, $this->config);
        $itemR = $dbc->execute($itemP, array($this->id));
        while ($itemW = $dbc->fetchRow($itemR)) {
            $bridge->removeItemFromPurchaseOrder($this->id, $itemW['trans_id'], $itemW['storeID']);
        }
        
        $cleanP = $dbc->prepare("DELETE FROM " . FannieDB::fqn('PendingSpecialOrder', 'trans') . "
                WHERE order_id=?");
        $dbc->execute($cleanP, array($this->id));

        return false;
    }

    protected function post_id_store_handler()
    {
        $dbc = $this->tdb();
        $soModel = new SpecialOrdersModel($dbc);
        $soModel->specialOrderID($this->id);
        $soModel->storeID($this->store);
        $soModel->save();

        $myStore = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $audit = $dbc->prepare('INSERT INTO ' . FannieDB::fqn('SpecialOrderEdits', 'trans') . '
            (specialOrderID, userID, tdate, action, detail, storeID) VALUES (?, ?, ?, ?, ?, ?)');
        $dbc->execute($audit, array($this->id, FannieAuth::getUID(), date('Y-m-d H:i:s'), 'Changed Store', 'Store #' . $this->store, $myStore));
    }

    protected function post_id_confirm_handler()
    {
        $dbc = $this->tdb();
        if ($this->confirm) {
            $ins = $dbc->prepare("INSERT INTO SpecialOrderHistory 
                                (order_id, entry_type, entry_date, entry_value)
                                VALUES
                                (?,'CONFIRMED',".$dbc->now().",'')");
            $dbc->execute($ins,array($this->id));
            echo date("M j Y g:ia");
        } else {
            $del = $dbc->prepare("DELETE FROM SpecialOrderHistory WHERE
                order_id=? AND entry_type='CONFIRMED'");
            $dbc->execute($del,array($this->id));
        }

        $myStore = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $audit = $dbc->prepare('INSERT INTO ' . FannieDB::fqn('SpecialOrderEdits', 'trans') . '
            (specialOrderID, userID, tdate, action, detail, storeID) VALUES (?, ?, ?, ?, ?, ?)');
        $dbc->execute($audit, array($this->id, FannieAuth::getUID(), date('Y-m-d H:i:s'), 'Toggled Confirm', ($this->confirm ? 'On' : 'Off'), $myStore));

        return false;
    }

    protected function post_id_pn_handler()
    {
        if ($this->pn == 0) {
            $this->pn = 1;
        }
        $dbc = $this->tdb();
        $prep = $dbc->prepare("UPDATE PendingSpecialOrder SET
            voided=? WHERE order_id=?");
        $dbc->execute($prep,array($this->pn,$this->id));

        $myStore = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $audit = $dbc->prepare('INSERT INTO ' . FannieDB::fqn('SpecialOrderEdits', 'trans') . '
            (specialOrderID, userID, tdate, action, detail, storeID) VALUES (?, ?, ?, ?, ?, ?)');
        $dbc->execute($audit, array($this->id, FannieAuth::getUID(), date('Y-m-d H:i:s'), 'Changed Household Name', 'Person #' . $this->pn, $myStore));

        return false;
    }

    protected function post_id_ctc_handler()
    {
        // skip save if no selection was made
        if (sprintf("%d", $this->ctc) !== "2") {
            $dbc = $this->tdb();
            // set numflag for CTC on trans_id=0 recrod
            $upP = $dbc->prepare("UPDATE PendingSpecialOrder SET
                numflag=? WHERE order_id=? AND trans_id=0");
            $dbc->execute($upP, array($this->ctc,$this->id));

            // update order status
            $this->status = $this->ctc == 1 ? 3 : 0;
            $this->post_id_status_handler();
        }

        return false;
    }

    protected function post_id_status_handler()
    {
        $dbc = $this->tdb();
        $timestamp = time();
        $soModel = new SpecialOrdersModel($dbc);
        $soModel->specialOrderID($this->id);
        $soModel->statusFlag($this->status);
        $soModel->subStatus($timestamp);
        $soModel->save();

        $json = array('tdate'=> date('m/d/Y'));
        if ($this->status == 5) {
            // check necessity
            $itemP = $dbc->prepare("SELECT COUNT(*) AS items FROM " . FannieDB::fqn('PendingSpecialOrder', 'trans') . " WHERE trans_id > 0 AND order_id=? AND deleted=0");
            $itemCount = $dbc->getValue($itemP, array($this->id));
            $sentP = $dbc->prepare("SELECT COUNT(*) FROM SpecialOrderCommLog WHERE specialOrderID = ? AND message LIKE '%arrived%'");
            $sentCount = $dbc->getValue($sentP, array($this->id));
            if ($itemCount > $sentCount) {
                $email = new OrderNotifications($dbc);
                $json['sentEmail'] = $email->orderArrivedEmail($this->id);
            }
        }

        $myStore = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $audit = $dbc->prepare('INSERT INTO ' . FannieDB::fqn('SpecialOrderEdits', 'trans') . '
            (specialOrderID, userID, tdate, action, detail, storeID) VALUES (?, ?, ?, ?, ?, ?)');
        $dbc->execute($audit, array($this->id, FannieAuth::getUID(), date('Y-m-d H:i:s'), 'Changed Status', 'Status #' . $this->status, $myStore));

        $this->runCallbacks($this->id);

        echo json_encode($json);

        return false;
    }

    protected function post_id_testNotify_handler()
    {
        $dbc = $this->tdb();
        $json = array();
        $email = new OrderNotifications($dbc);
        $json['sentEmail'] = $email->orderTestEmail($this->id);

        echo json_encode($json);

        return false;
    }

    protected function post_id_nodupe_handler()
    {
        $dbc = $this->tdb();
        $prep = $dbc->prepare('UPDATE SpecialOrders SET noDuplicate=? WHERE specialOrderID=?');
        $res = $dbc->execute($prep, array($this->nodupe ? 1 : 0, $this->id));

        $myStore = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $audit = $dbc->prepare('INSERT INTO ' . FannieDB::fqn('SpecialOrderEdits', 'trans') . '
            (specialOrderID, userID, tdate, action, detail, storeID) VALUES (?, ?, ?, ?, ?, ?)');
        $dbc->execute($audit, array($this->id, FannieAuth::getUID(), date('Y-m-d H:i:s'), 'Changed Duplication', ($this->nodupe ? 'Off' : 'On'), $myStore));

        echo 'Done';

        return false;
    }

    public function post_upc_discounttype_handler()
    {
        $discountType = FormLib::get('discounttype');
        $isMember = FormLib::get('isMember');
        $id = FormLib::get('orderid');
        $srp = FormLib::get('srp');
        $qty = FormLib::get('qty');
        $upc = FormLib::get('upc');
        $upc = BarcodeLib::padUPC($upc);
        $newPrice;
        $dbc = $this->tdb();

        $discTypeP = $dbc->prepare('UPDATE PendingSpecialOrder SET discounttype=? WHERE order_id=? AND upc=?');
        $discTypeR = $dbc->execute($discTypeP, array($discountType, $id, $upc));

        $casesA = array($id, $upc);
        $casesP = $dbc->prepare("SELECT ItemQtty FROM PendingSpecialOrder WHERE order_id=? AND upc=? AND deleted = 0");
        $casesR = $dbc->execute($casesP, $casesA);
        while ($row = $dbc->fetchRow($casesR)) {
            $cases = $row['ItemQtty'];
        }

        if ($discountType == 1) {
            $dbc = $this->odb();

            $spP = $dbc->prepare("SELECT bl.salePrice, b.batchName
                FROM batchList bl
                    LEFT JOIN batches b ON b.batchID=bl.batchID
                WHERE bl.upc = ? 
                    AND b.batchType = 1
                    AND b.startDate <= NOW()
                    AND b.endDate >= NOW()
                LIMIT 1");
            $spR = $dbc->execute($spP, array($upc));
            $spW = $dbc->fetchRow($spR);
            if (isset($spW['salePrice'])) {
                $batchName = $spW['batchName'];
                $specialPrice = $spW['salePrice'] * $qty * $cases;
                $newPrice = $specialPrice;
                if (strpos($batchName, 'Co-op Deals') !== false && strpos($batchName, 'TPR') == false && strpos($batchName, 'BOGO') == false) {
                    // then item is Coop Deal, gets extra 10% 
                    if ($isMember == 'PC') {
                        $newPrice = $specialPrice * 0.9;
                    }
                } else if (strpos($batchName, 'Co-op Deals') !== false && strpos($batchName, 'TPR') == false && strpos($batchName, 'BOGO') == true) {
                    $newPrice = $srp - (floor($qty/2) * ($srp / $qty));
                }
            } else {
                $curSetP = $dbc->prepare("
                    SELECT i.dealSet,
                    b.batchName,
                    SUBSTRING(b.batchName, LOCATE(i.dealSet, b.batchName)-2, 1) as CurCycle
                      FROM batches b
                    INNER JOIN CoopDealsItems i ON INSTR(b.batchName, i.dealSet)
                    WHERE b.startDate <= DATE(NOW()) AND b.endDate >= DATE(NOW()) LIMIT 1
                ");
                $curSetR = $dbc->execute($curSetP);
                $curSetW = $dbc->fetchRow($curSetR);
                $curDealSet = $curSetW['dealSet'];
                $curCycleChr = $curSetW['CurCycle'];

                $cdiA = array($upc, $curDealSet);
                $cdiP = $dbc->prepare("SELECT price, abtpr FROM CoopDealsItems WHERE upc = ? AND dealSet = ?");
                $cdiR = $dbc->execute($cdiP, $cdiA);
                $cdiW = $dbc->fetchRow($cdiR);
                $abtpr = $cdiW['abtpr'];
                $newPrice = $cdiW['price'] * $qty;
                if (str_contains($abtpr, $curCycleChr)) {
                    // then item is Coop Deal, gets extra 10% 
                    if ($isMember == 'PC') {
                        $newPrice = $newPrice * 0.9;
                    }
                }

            }


        } else {
            if ($isMember == 'PC') {
                $newPrice = $srp * 0.85;
            } else {
                $newPrice = $srp;
            }
        }
        $newPrice = round($newPrice, 2, PHP_ROUND_HALF_UP);
        $newPercent = (1 - ($newPrice / $srp)) * 100;
        $newPercent = round($newPercent, 0);

        $json = array(
            'newPrice' => $newPrice,
            'newPercent' => $newPercent,
            'dealSet' => isset($curDealSet) ? $curDealSet : '',
            'test' => isset($test) ? $test : '',
        );
        echo json_encode($json);

        return false;
    }

    private function runCallbacks($orderID)
    {
        $callbacks = $this->config->get('SPO_CALLBACKS');
        foreach ($callbacks as $cb) {
            $obj = new $cb();
            $dbc = $this->tdb();
            $prep = $dbc->prepare("SELECT trans_id FROM " . FannieDB::fqn('PendingSpecialOrder', 'trans') . " WHERE order_id=? AND trans_id > 0 AND deleted=0");
            $res = $dbc->execute($prep, array($orderID));
            while ($row = $dbc->fetchRow($res)) {
                $obj->run($orderID, $row['trans_id']);
            }
        }
    }

    public function unitTest($phpunit)
    {
        $this->connection->throwOnFailure(true);
        $this->id = 9999;
        $this->close = 9;
        $this->post_id_close_handler();
        $this->store = 1;
        $this->post_id_store_handler();
        $this->confirm = 1;
        $this->post_id_confirm_handler();
        $this->confirm = 0;
        $this->post_id_confirm_handler();
        $this->pn = 0;
        $this->post_id_pn_handler();
        $this->ctc = 1;
        $this->post_id_ctc_handler();
    }
}

FannieDispatch::conditionalExec();

