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

class OrderAjax extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute(
            'post<id><status>', 
            'post<id><ctc>',
            'post<id><pn>',
            'post<id><confirm>',
            'post<id><store>',
            'post<id><close>',
            'post<id><testNotify>'
        );

        return parent::preprocess();
    }

    private function tdb()
    {
        $this->connection->selectDB($this->config->get('TRANS_DB'));
        return $this->connection;
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
        
        $cleanP = $dbc->prepare("DELETE FROM PendingSpecialOrder
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
            $email = new OrderNotifications($dbc);
            $json['sentEmail'] = $email->orderArrivedEmail($this->id);
        }

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

