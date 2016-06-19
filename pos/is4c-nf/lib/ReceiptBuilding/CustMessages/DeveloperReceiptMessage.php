<?php

namespace COREPOS\pos\lib\ReceiptBuilding\CustMessages;
use COREPOS\pos\lib\ReceiptLib;

class DeveloperReceiptMessage extends CustomerReceiptMessage 
{
    public function message($str)
    {
        $ret = "\n";
        $ret .= ReceiptLib::biggerFont(ReceiptLib::centerBig('Save $5 on a ')) . "\n";
        $ret .= ReceiptLib::biggerFont(ReceiptLib::centerBig('purchase of $25 or more')) . "\n\n";
        $expires = strtotime('+30 days');
        $ret .= ReceiptLib::centerString('Expires: ' . date('m/d/Y', $expires)) . "\n"; 
        $barcode = 'RC' . date('ym', $expires) . '009';
        $ret .= ReceiptLib::code39($barcode); 

        return array('print'=>$ret, 'any'=>'');
    }

}

