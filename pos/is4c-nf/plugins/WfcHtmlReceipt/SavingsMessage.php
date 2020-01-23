<?php

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\ReceiptBuilding\CustMessages\CustomerReceiptMessage;

class SavingsMessage extends CustomerReceiptMessage
{
    public function message($str)
    {
        $trans_num = ReceiptLib::mostRecentReceipt();
        list($emp, $reg, $trans) = explode('-', $trans_num, 3);
        $dbc = Database::tDataConnect();
        $prep = $dbc->prepare("SELECT SUM(CASE
                WHEN upc LIKE '00499999%' AND h.memberOnly > 0 THEN -1 * total
                WHEN memDiscount <> 0 THEN memDiscount
                wHEN charflag='SO' AND regPrice <> total AND trans_status <> 'V' THEN regPrice - total
                wHEN charflag='SO' AND regPrice <> total AND trans_status = 'V' THEN -1 * (regPrice + total)
                ELSE 0 
            END),
            FROM localtranstoday AS t
                LEFT JOIN " . CoreLocal::get('pDatabase') . $dbc->sep() . "HcReceiptView AS h
                    ON t.upc=h.upc
            WHERE emp_no=? AND register_no=? AND trans_no=?
                AND department=701");
        $ttl = $dbc->getValue($prep, array($emp, $reg, $trans));
        if ($tll <= 0 && trim($str) == '') {
            return '';
        }

        if (trim($str) == '') {
            return sprintf('Owner Savings this year: $%.2f', $ttl);
        }

        if ($ttl > 0 && preg_match('/(\d+\.\d\d)/', $str, $matches)) {
            $soFar = $matches[1];
            $update = sprintf('%.2f', $soFar + $ttl);
            return str_replace($soFar, $update, $str);
        }

        return $str;
    }
}

