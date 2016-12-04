<?php

class SpecialOrderLib
{
    public function __construct($dbc, $config)
    {
        $this->dbc = $dbc;
        $this->config = $config;
    }

    public function createEmptyOrder()
    {
        $dbc = $this->dbc;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();
        $user = FannieAuth::checkLogin();
        $orderID = 1;
        $values = ($this->config->get('SERVER_DBMS') != "MSSQL" ? "VALUES()" : "DEFAULT VALUES");
        $dbc->query('INSERT ' . $TRANS . 'SpecialOrders ' . $values);
        $orderID = $dbc->insertID();

        $ins_array = $this->genericRow($orderID);
        $ins_array['numflag'] = 2;
        $ins_array['mixMatch'] = $user;
        $dbc->smartInsert("{$TRANS}PendingSpecialOrder",$ins_array);

        $note_vals = array(
            'order_id'=>$orderID,
            'notes'=>"",
            'superID'=>0
        );

        $status_vals = array(
            'order_id'=>$orderID,
            'status_flag'=>3,
            'sub_status'=>time()
        );

        $dbc->selectDB($this->config->get('TRANS_DB'));
        $s_order = new SpecialOrdersModel($dbc);
        $s_order->specialOrderID($orderID);
        $s_order->statusFlag($status_vals['status_flag']);
        $s_order->subStatus($status_vals['sub_status']);
        $s_order->notes(trim($note_vals['notes'],"'"));
        $s_order->noteSuperID($note_vals['superID']);
        $s_order->save();
        $dbc->selectDB($this->config->get('TRANS_DB')); // switch back to previous

        $this->createContactRow($orderID);

        return $orderID;
    }

    public function genericRow($orderID)
    {
        return array(
        'order_id'=>$orderID,
        'datetime'=>date('Y-m-d H:i:s'),
        'emp_no'=>1001,
        'register_no'=>30,
        'trans_no'=>$orderID,
        'upc'=>'0',
        'description'=>"SPECIAL ORDER",
        'trans_type'=>"C",
        'trans_subtype'=>"",
        'trans_status'=>"",
        'department'=>0,
        'quantity'=>0,
        'scale'=>0,
        'cost'=>0,
        'unitPrice'=>0,
        'total'=>0,
        'regPrice'=>0,
        'tax'=>0,
        'foodstamp'=>0,
        'discount'=>0,
        'memDiscount'=>0,
        'discountable'=>1,
        'discounttype'=>0,
        'voided'=>0,
        'percentDiscount'=>0,
        'ItemQtty'=>0,
        'volDiscType'=>0,
        'volume'=>0,
        'VolSpecial'=>0,
        'mixMatch'=>0,
        'matched'=>0,
        'memType'=>0,
        'staff'=>0,
        'numflag'=>0,
        'charflag'=>"",   
        'card_no'=>0,
        'trans_id'=>0
        );
    }

    private function createContactRow($orderID)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $so_order = new SpecialOrdersModel($dbc);
        $so_order->specialOrderID($orderID);
        $so_order->firstName('');
        $so_order->lastName('');
        $so_order->street('');
        $so_order->city('');
        $so_order->state('');
        $so_order->zip('');
        $so_order->phone('');
        $so_order->altPhone('');
        $so_order->email('');
        $so_order->save();

        $dbc->selectDB($this->config->get('OP_DB'));
    }
}

