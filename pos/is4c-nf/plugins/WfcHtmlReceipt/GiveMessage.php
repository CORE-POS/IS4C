<?php

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\ReceiptBuilding\CustMessages\CustomerReceiptMessage;

class GiveMessage extends CustomerReceiptMessage
{
    public function message($str)
    {
        $trans_num = ReceiptLib::mostRecentReceipt();
        list($emp, $reg, $trans) = explode('-', $trans_num, 3);
        $dbc = Database::tDataConnect();
        $prep = $dbc->prepare("SELECT SUM(total) FROM localtranstoday
            WHERE emp_no=? AND register_no=? AND trans_no=?
                AND department=701");
        $ttl = $dbc->getValue($prep, array($emp, $reg, $trans));
        if ($tll <= 0 && trim($str) == '') {
            return '';
        }

        if (trim($str) == '') {
            return sprintf('GIVE Donations this year: $%.2f', $ttl);
        }

        if ($ttl > 0 && preg_match('/(\d+\.\d\d)/', $str, $matches)) {
            $soFar = $matches[1];
            $update = sprintf('%.2f', $soFar + $ttl);
            return str_replace($soFar, $update, $str);
        }

        return $str;
    }
}

