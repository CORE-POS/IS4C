<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class B2BTask extends FannieTask 
{
    public $name = 'B2B Invoice Finalizer';
    public $description = 'Marks B2B invoices as paid';

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));

        $upP = $dbc->prepare("UPDATE B2BInvoices SET isPaid=1, paidDate=?, paidTransNum=? WHERE b2bInvoiceID=?");

        // get transaction lines flagged as B2B payments
        // that haven't been voided
        $query = "SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                trans_num,
                MAX(tdate) as rdate,
                MAX(numflag) AS b2bID
            FROM dlog_15
            WHERE charflag='B2'
                AND trans_type='D'
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                trans_num
            HAVING SUM(total) <> 0";
        $res = $dbc->query($query);

        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($res)) {
            $args = array(
                $row['rdate'],
                $row['trans_num'],
                $row['b2bID'],
            );
            $dbc->execute($upP, $args);
        }
        $dbc->commitTransaction();
    }
}

