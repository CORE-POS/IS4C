<?php

namespace COREPOS\pos\lib\ReceiptBuilding\Messages;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

class WicReceiptMessage extends ReceiptMessage
{
    public function select_condition()
    {
        return "MAX(CASE WHEN trans_subtype IN ('EW') THEN trans_id ELSE 0 END)";
    }

    public function message($val, $ref, $reprint=False)
    {
        $date = date('Ymd');
        list($emp, $reg, $trans) = ReceiptLib::parseRef($ref);

        $dbc = Database::tDataConnect();
        $emvP = $dbc->prepare('
            SELECT content
            FROM EmvReceipt
            WHERE dateID=?
                AND empNo=?
                AND registerNo=?
                AND transNo=?
                AND transID=?
        ');
        $ret = $dbc->getValue($emvP, array($date, $emp, $reg, $trans, $val));

        return $ret ? $ret : '';
    }
}

